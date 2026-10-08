<?php

namespace App\Livewire\Public;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\FaceSearch;
use App\Services\CartService;
use App\Services\EventAnalyticsService;
use App\Services\FaceRecognition\NoFaceDetectedException;
use App\Services\FaceRecognitionService;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;

class SelfieSearch extends Component
{
    use WithFileUploads, WithPagination;

    public Event $event;

    #[Validate('required|image|mimes:jpg,jpeg,png,webp|max:10240')]
    public mixed $selfie = null;

    #[Validate('accepted')]
    public bool $consent_accepted = false;

    public string $participant_query = '';

    public array $results = [];

    public bool $has_searched = false;

    public string $result_source = '';

    public string $search_mode = 'selfie';

    public function search(FaceRecognitionService $face_recognition_service): void
    {
        $this->validate();

        if ($this->too_many_search_attempts()) {
            return;
        }

        $path = $this->selfie->store("events/{$this->event->id}/selfies", config('filesystems.default'));
        $face_search = FaceSearch::query()->create([
            'event_id' => $this->event->id,
            'selfie_path' => $path,
            'status' => 'processing',
            'consent_accepted' => $this->consent_accepted,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => now()->addHours(config('fotx.face_selfie_ttl_hours', 24)),
        ]);

        try {
            $results = $face_recognition_service->search_by_selfie($this->event, $path);
        } catch (NoFaceDetectedException) {
            $face_search->update(['status' => 'failed']);
            $this->addError('selfie', 'Não encontramos um rosto nesta selfie. Tente outra foto, de frente e com boa iluminação.');

            return;
        } catch (AwsException|RuntimeException $exception) {
            report($exception);
            $face_search->update(['status' => 'failed']);
            $this->addError('selfie', 'Não foi possível buscar suas fotos agora. Tente novamente em instantes.');

            return;
        }

        $face_search->update(['status' => 'done', 'results' => $results]);

        $photo_ids = collect($results)->pluck('event_photo_id')->all();
        $photos = EventPhoto::query()->whereIn('id', $photo_ids)->get()->keyBy('id');

        $this->results = collect($results)
            ->map(fn (array $result): array => [
                'score' => $result['score'],
                'photo' => $photos[$result['event_photo_id']] ?? null,
                'source' => 'selfie',
            ])
            ->filter(fn (array $result): bool => $result['photo'] !== null)
            ->values()
            ->all();

        $this->has_searched = true;
        $this->result_source = 'selfie';

        app(EventAnalyticsService::class)->record(
            event: $this->event,
            type: 'selfie_search',
            source: 'public_event',
            metadata: ['results_count' => count($this->results)],
        );
    }

    public function search_by_text(): void
    {
        $validated = $this->validate([
            'participant_query' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $term = trim((string) $validated['participant_query']);
        $like_term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
        $normalized_term = mb_strtolower($term);

        $photos = $this->event->ready_photos()
            ->where(function ($query) use ($like_term): void {
                $query
                    ->where('participant_code', 'like', $like_term)
                    ->orWhere('search_keywords', 'like', $like_term)
                    ->orWhere('filename', 'like', $like_term);
            })
            ->limit(24)
            ->get();

        $this->results = $photos
            ->map(function (EventPhoto $event_photo) use ($normalized_term): array {
                $participant_code = mb_strtolower((string) $event_photo->participant_code);
                $score = $participant_code === $normalized_term ? 1.0 : 0.82;

                return [
                    'score' => $score,
                    'photo' => $event_photo,
                    'source' => 'text',
                ];
            })
            ->values()
            ->all();

        $this->has_searched = true;
        $this->result_source = 'text';

        app(EventAnalyticsService::class)->record(
            event: $this->event,
            type: 'text_search',
            source: 'public_event',
            metadata: [
                'query' => $term,
                'results_count' => count($this->results),
            ],
        );
    }

    private function too_many_search_attempts(): bool
    {
        $key = 'face-search:'.$this->event->id.':'.sha1((string) request()->ip().'|'.session()->getId());
        $max_attempts = config('fotx.face_search_max_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $max_attempts)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('selfie', "Muitas buscas em sequência. Tente novamente em {$seconds} segundos.");

            return true;
        }

        RateLimiter::hit($key, config('fotx.face_search_decay_minutes', 10) * 60);

        return false;
    }

    public function add_to_cart(string $event_photo_public_id, CartService $cart_service): void
    {
        $event_photo = EventPhoto::query()
            ->with('event')
            ->where('event_id', $this->event->id)
            ->where('public_id', $event_photo_public_id)
            ->firstOrFail();
        $cart_service->add_photo($event_photo);
        $this->dispatch('cart-updated');
    }

    public function remove_from_cart(string $event_photo_public_id, CartService $cart_service): void
    {
        $cart_service->remove_public_photo($event_photo_public_id);
        $this->dispatch('cart-updated');
    }

    public function set_search_mode(string $search_mode): void
    {
        $this->search_mode = $search_mode === 'text' ? 'text' : 'selfie';
        $this->resetErrorBag();
    }

    public function render(CartService $cart_service)
    {
        return view('livewire.public.selfie-search', [
            'cart_photo_ids' => $cart_service->get_items()->pluck('event_photo_id')->all(),
            'cart_count' => $cart_service->count(),
            'cart_total' => $cart_service->total(),
            'discount_percent' => $cart_service->discount_percent(),
            'next_discount' => $cart_service->next_discount(),
            'gallery_photos' => $this->event->public_gallery
                ? $this->event->ready_photos()->latest('id')->paginate(24, pageName: 'pagina')
                : null,
        ]);
    }
}
