<?php

namespace App\Livewire\Public;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\FaceSearch;
use App\Models\PhotoFace;
use App\Services\CartService;
use App\Services\EventAnalyticsService;
use App\Services\FaceRecognition\NoFaceDetectedException;
use App\Services\FaceRecognitionService;
use Aws\Exception\AwsException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Busca por selfie em todos os eventos da vitrine, sem o comprador escolher um evento antes.
 */
class CatalogSelfieSearch extends Component
{
    use WithFileUploads;

    #[Validate('required|image|mimes:jpg,jpeg,png,webp|max:10240')]
    public mixed $selfie = null;

    #[Validate('accepted')]
    public bool $consent_accepted = false;

    /** @var list<array{event: Event, photos: list<array{photo: EventPhoto, score: float}>}> */
    public array $results_by_event = [];

    public bool $has_searched = false;

    public int $searched_events_count = 0;

    public function search(FaceRecognitionService $face_recognition_service): void
    {
        $this->validate();

        if ($this->too_many_search_attempts()) {
            return;
        }

        $events = $this->searchable_events();
        $this->results_by_event = [];
        $this->has_searched = false;

        // Sem eventos para comparar, a selfie nem é guardada.
        if ($events->isEmpty()) {
            $this->mark_searched($events);

            return;
        }

        $path = $this->selfie->store('catalog/selfies', config('filesystems.default'));
        $face_search = FaceSearch::query()->create([
            'scope' => 'catalog',
            'selfie_path' => $path,
            'status' => 'processing',
            'consent_accepted' => $this->consent_accepted,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => now()->addHours(config('fotx.face_selfie_ttl_hours', 24)),
        ]);

        try {
            $results = array_slice(
                $face_recognition_service->search_by_selfie_in_events($events, $path),
                0,
                config('fotx.catalog_face_search_max_results', 100),
            );
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

        $this->results_by_event = $this->group_by_event($results, $events);
        $this->mark_searched($events);

        foreach ($this->results_by_event as $group) {
            app(EventAnalyticsService::class)->record(
                event: $group['event'],
                type: 'selfie_search',
                source: 'catalog',
                metadata: ['results_count' => count($group['photos'])],
            );
        }
    }

    public function add_to_cart(string $event_photo_public_id, CartService $cart_service): void
    {
        $event_photo = EventPhoto::query()
            ->with('event')
            ->where('public_id', $event_photo_public_id)
            ->where('status', 'ready')
            ->whereHas('event', fn ($query) => $query->inCatalog())
            ->firstOrFail();

        if (! $cart_service->add_photo($event_photo)) {
            $this->addError('cart', $cart_service->other_event_message());

            return;
        }

        $this->resetErrorBag('cart');
        $this->dispatch('cart-updated');
    }

    public function remove_from_cart(string $event_photo_public_id, CartService $cart_service): void
    {
        $cart_service->remove_public_photo($event_photo_public_id);
        $this->resetErrorBag('cart');
        $this->dispatch('cart-updated');
    }

    /**
     * Eventos da vitrine com rostos já indexados, dos mais recentes para os mais antigos,
     * limitados para controlar o custo de cada busca.
     *
     * @return Collection<int, Event>
     */
    private function searchable_events(): Collection
    {
        return Event::query()
            ->inCatalog()
            ->whereIn('id', PhotoFace::query()->select('event_id')->whereNotNull('provider_face_id'))
            ->orderByRaw('event_date is null')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->limit(config('fotx.catalog_face_search_max_events', 30))
            ->get();
    }

    /**
     * @param  list<array{event_id: int, event_photo_id: int, score: float}>  $results
     * @param  Collection<int, Event>  $events
     * @return list<array{event: Event, photos: list<array{photo: EventPhoto, score: float}>}>
     */
    private function group_by_event(array $results, Collection $events): array
    {
        $photos = EventPhoto::query()->whereIn('id', array_column($results, 'event_photo_id'))->get()->keyBy('id');
        $events = $events->keyBy('id');

        return collect($results)
            ->filter(fn (array $result): bool => $photos->has($result['event_photo_id']))
            ->groupBy('event_id')
            ->map(fn (Collection $event_results, int $event_id): array => [
                'event' => $events->get($event_id),
                'photos' => $event_results
                    ->map(fn (array $result): array => [
                        'photo' => $photos->get($result['event_photo_id']),
                        'score' => $result['score'],
                    ])
                    ->values()
                    ->all(),
            ])
            ->sortByDesc(fn (array $group): float => $group['photos'][0]['score'])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Event>  $events
     */
    private function mark_searched(Collection $events): void
    {
        $this->has_searched = true;
        $this->searched_events_count = $events->count();
    }

    private function too_many_search_attempts(): bool
    {
        $key = 'catalog-face-search:'.sha1((string) request()->ip().'|'.session()->getId());
        $max_attempts = config('fotx.catalog_face_search_max_attempts', 3);

        if (RateLimiter::tooManyAttempts($key, $max_attempts)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('selfie', "Muitas buscas em sequência. Tente novamente em {$seconds} segundos.");

            return true;
        }

        RateLimiter::hit($key, config('fotx.face_search_decay_minutes', 10) * 60);

        return false;
    }

    public function render(CartService $cart_service)
    {
        return view('livewire.public.catalog-selfie-search', [
            'cart_photo_ids' => $cart_service->get_items()->pluck('event_photo_id')->all(),
            'cart_count' => $cart_service->count(),
            'cart_total' => $cart_service->total(),
            'discount_percent' => $cart_service->discount_percent(),
            'next_discount' => $cart_service->next_discount(),
        ])->layout('layouts.public', [
            'title' => 'Encontre suas fotos com uma selfie',
            'description' => 'Envie uma selfie e encontre suas fotos em todos os eventos da vitrine do Fotx.',
        ]);
    }
}
