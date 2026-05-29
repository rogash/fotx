<?php

namespace App\Livewire\Public;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class Cart extends Component
{
    #[On('cart-updated')]
    public function refresh_cart(): void {}

    public function remove_photo(string $event_photo_public_id, CartService $cart_service): void
    {
        $cart_service->remove_public_photo($event_photo_public_id);
    }

    public function render(CartService $cart_service)
    {
        return view('livewire.public.cart', [
            'items' => $cart_service->get_items(),
            'summary' => $cart_service->summary(),
            'total' => $cart_service->total(),
        ])->layout('layouts.public');
    }
}
