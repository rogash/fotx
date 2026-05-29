<?php

namespace App\Livewire\Public;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Services\CartService;
use Livewire\Component;

class PhotoDetail extends Component
{
    public Event $event;

    public EventPhoto $event_photo;

    public function mount(string $slug, EventPhoto $event_photo): void
    {
        $this->event = Event::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        abort_if($event_photo->event_id !== $this->event->id || $event_photo->status !== 'ready', 404);

        $this->event_photo = $event_photo;
    }

    public function add_to_cart(CartService $cart_service): void
    {
        $cart_service->add_photo($this->event_photo->load('event'));
        $this->dispatch('cart-updated');
        session()->flash('status', 'Foto adicionada ao carrinho.');
    }

    public function remove_from_cart(CartService $cart_service): void
    {
        $cart_service->remove_photo($this->event_photo->id);
        $this->dispatch('cart-updated');
        session()->flash('status', 'Foto removida do carrinho.');
    }

    public function render(CartService $cart_service)
    {
        return view('livewire.public.photo-detail', [
            'is_in_cart' => $cart_service->has_photo($this->event_photo->id),
            'cart_count' => $cart_service->count(),
        ])->layout('layouts.public');
    }
}
