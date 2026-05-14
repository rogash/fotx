<?php

namespace App\Livewire\Public;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    #[On('cart-updated')]
    public function refresh_cart(): void {}

    public function render(CartService $cart_service)
    {
        return view('livewire.public.cart-badge', [
            'cart_count' => $cart_service->count(),
        ]);
    }
}
