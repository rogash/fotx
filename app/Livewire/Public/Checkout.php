<?php

namespace App\Livewire\Public;

use App\Models\Order;
use App\Services\CartService;
use App\Services\Payments\PaymentGatewayManager;
use Livewire\Component;

class Checkout extends Component
{
    public ?string $buyer_name = null;

    public string $buyer_email = '';

    public function start_payment(CartService $cart_service, PaymentGatewayManager $payment_gateway_manager): void
    {
        $validated = $this->validate([
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'buyer_email' => ['required', 'email', 'max:255'],
        ]);

        $items = $cart_service->get_items();
        abort_if($items->isEmpty(), 422);

        $event_id = $items->first()['event_id'];
        abort_if($items->pluck('event_id')->unique()->count() > 1, 422, 'O carrinho deve ter fotos de um único evento.');

        $order = Order::query()->create([
            ...$validated,
            'event_id' => $event_id,
            'total_amount' => $cart_service->total(),
            'status' => 'pending',
        ]);

        foreach ($items as $item) {
            $order->items()->create([
                'event_photo_id' => $item['event_photo_id'],
                'price' => $item['price'],
            ]);
        }

        $checkout_data = $payment_gateway_manager->gateway()->create_checkout($order->load(['event', 'items.event_photo']));
        $order->update([
            'payment_provider' => $checkout_data->provider,
            'payment_reference' => $checkout_data->reference,
            'payment_checkout_url' => $checkout_data->checkout_url,
        ]);

        $cart_service->clear();

        $this->redirectRoute('orders.pending', [$order, $order->download_token], navigate: true);
    }

    public function render(CartService $cart_service)
    {
        return view('livewire.public.checkout', [
            'items' => $cart_service->get_items(),
            'total' => $cart_service->total(),
        ])->layout('layouts.public');
    }
}
