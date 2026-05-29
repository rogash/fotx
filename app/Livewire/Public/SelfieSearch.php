<?php

namespace App\Livewire\Public;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\FaceSearch;
use App\Services\CartService;
use App\Services\EventAnalyticsService;
use App\Services\FaceRecognitionService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class SelfieSearch extends Component
{
    use WithFileUploads;

    public Event $event;

    #[Validate('required|image|mimes:jpg,jpeg,png,webp|max:10240')]
    public mixed $selfie = null;

    #[Validate('accepted')]
    public bool $consent_accepted = false;

    public string $participant_query = '';

    public array $results = [];

    public bool $has_searched = false;

    public string $result_source = '';

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

        $results = $face_recognition_service->search_by_selfie($this->event, $path);
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
        session()->flash('status', 'Foto adicionada ao carrinho.');
    }

    public function remove_from_cart(string $event_photo_public_id, CartService $cart_service): void
    {
        $cart_service->remove_public_photo($event_photo_public_id);
        $this->dispatch('cart-updated');
        session()->flash('status', 'Foto removida do carrinho.');
    }

    public function render(CartService $cart_service)
    {
        return view('livewire.public.selfie-search', [
            'cart_photo_ids' => $cart_service->get_items()->pluck('event_photo_id')->all(),
            'cart_count' => $cart_service->count(),
        ]);
    }
}
