<?php

namespace App\Livewire\Public;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\FaceSearch;
use App\Services\CartService;
use App\Services\FaceRecognitionService;
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

    public array $results = [];

    public bool $has_searched = false;

    public function search(FaceRecognitionService $face_recognition_service): void
    {
        $this->validate();

        $path = $this->selfie->store("events/{$this->event->id}/selfies", config('filesystems.default'));
        $face_search = FaceSearch::query()->create([
            'event_id' => $this->event->id,
            'selfie_path' => $path,
            'status' => 'processing',
            'consent_accepted' => $this->consent_accepted,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $results = $face_recognition_service->search_by_selfie($this->event, $path);
        $face_search->update(['status' => 'done', 'results' => $results]);

        $photo_ids = collect($results)->pluck('event_photo_id')->all();
        $photos = EventPhoto::query()->whereIn('id', $photo_ids)->get()->keyBy('id');

        $this->results = collect($results)
            ->map(fn (array $result): array => [
                'score' => $result['score'],
                'photo' => $photos[$result['event_photo_id']] ?? null,
            ])
            ->filter(fn (array $result): bool => $result['photo'] !== null)
            ->values()
            ->all();

        $this->has_searched = true;
    }

    public function add_to_cart(int $event_photo_id, CartService $cart_service): void
    {
        $event_photo = EventPhoto::query()->with('event')->where('event_id', $this->event->id)->findOrFail($event_photo_id);
        $cart_service->add_photo($event_photo);
        $this->dispatch('cart-updated');
        session()->flash('status', 'Foto adicionada ao carrinho.');
    }

    public function remove_from_cart(int $event_photo_id, CartService $cart_service): void
    {
        $cart_service->remove_photo($event_photo_id);
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
