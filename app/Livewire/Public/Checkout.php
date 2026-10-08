<?php

namespace App\Livewire\Public;

use App\Models\Order;
use App\Services\CartService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use RuntimeException;

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
        $summary = $cart_service->summary();

        try {
            // Se o gateway falhar, o rollback evita pedidos pendentes sem link de pagamento.
            $order = DB::transaction(function () use ($validated, $event_id, $summary, $items, $payment_gateway_manager): Order {
                $order = Order::query()->create([
                    ...$validated,
                    'event_id' => $event_id,
                    'total_amount' => $summary['total'],
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

                return $order;
            });
        } catch (RuntimeException|ConnectionException $exception) {
            report($exception);
            $this->addError('payment', 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.');

            return;
        }

        $cart_service->clear();

        $this->redirectRoute('orders.pending', [$order, $order->download_token], navigate: true);
    }

    public function remove_photo(string $event_photo_public_id, CartService $cart_service): void
    {
        $cart_service->remove_public_photo($event_photo_public_id);
        $this->dispatch('cart-updated');
    }

    public function render(CartService $cart_service)
    {
        $items = $cart_service->get_items();

        return view('livewire.public.checkout', [
            'items' => $items,
            'summary' => $cart_service->summary(),
            'total' => $cart_service->total(),
            'next_discount' => $cart_service->next_discount(),
            'event' => $items->first()['photo']->event ?? null,
        ])->layout('layouts.public', ['title' => 'Finalizar compra']);
    }
}
