<?php

namespace App\Livewire\Public;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class Cart extends Component
{
    #[On('cart-updated')]
    public function refresh_cart(): void {}

    public function remove_photo(int $event_photo_id, CartService $cart_service): void
    {
        $cart_service->remove_photo($event_photo_id);
    }

    public function render(CartService $cart_service)
    {
        return view('livewire.public.cart', [
            'items' => $cart_service->get_items(),
            'total' => $cart_service->total(),
        ])->layout('layouts.public');
    }
}
