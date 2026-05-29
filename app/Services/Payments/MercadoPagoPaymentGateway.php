<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoPaymentGateway implements PaymentGateway
{
    public function create_checkout(Order $order): PaymentCheckoutData
    {
        $access_token = config('fotx.mercado_pago_access_token');

        if (blank($access_token)) {
            throw new RuntimeException('MERCADO_PAGO_ACCESS_TOKEN não configurado.');
        }

        $order->loadMissing(['event', 'items.event_photo']);

        $response = Http::withToken($access_token)
            ->acceptJson()
            ->withHeaders(array_filter([
                'X-Integrator-Id' => config('fotx.mercado_pago_integrator_id'),
            ]))
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => $order->items->map(fn ($item): array => [
                    'id' => (string) $item->event_photo->public_id,
                    'title' => $item->event_photo->filename,
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => (float) $item->price,
                ])->values()->all(),
                'payer' => [
                    'name' => $order->buyer_name,
                    'email' => $order->buyer_email,
                ],
                'external_reference' => (string) $order->public_id,
                'notification_url' => route('payments.mercado-pago.webhook'),
                'back_urls' => [
                    'success' => route('orders.pending', [$order, $order->download_token]),
                    'pending' => route('orders.pending', [$order, $order->download_token]),
                    'failure' => route('orders.pending', [$order, $order->download_token]),
                ],
                'auto_return' => 'approved',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Falha ao criar preferencia no Mercado Pago: '.$response->body());
        }

        $payload = $response->json();

        return new PaymentCheckoutData(
            provider: 'mercado_pago',
            reference: (string) $payload['id'],
            checkout_url: (string) ($payload['init_point'] ?? $payload['sandbox_init_point'] ?? ''),
        );
    }
}
