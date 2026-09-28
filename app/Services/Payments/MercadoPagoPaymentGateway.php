<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;

class MercadoPagoPaymentGateway implements PaymentGateway
{
    private const API_URL = 'https://api.mercadopago.com';

    public function create_checkout(Order $order): PaymentCheckoutData
    {
        $order->loadMissing(['event', 'items']);

        // Um único item com o total do pedido garante que o desconto por volume
        // seja cobrado exatamente como exibido no carrinho.
        $response = $this->api()->post(self::API_URL.'/checkout/preferences', [
            'items' => [[
                'id' => (string) $order->public_id,
                'title' => $this->checkout_item_title($order),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $order->total_amount,
            ]],
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

    /**
     * Valida o header x-signature conforme a documentação do Mercado Pago:
     * HMAC-SHA256 do manifesto "id:{data.id};request-id:{x-request-id};ts:{ts};".
     */
    public function has_valid_webhook_signature(Request $request): bool
    {
        $secret = config('fotx.mercado_pago_webhook_secret');

        if (blank($secret)) {
            throw new RuntimeException('MERCADO_PAGO_WEBHOOK_SECRET não configurado.');
        }

        $signature_parts = collect(explode(',', (string) $request->header('x-signature')))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

                return [$key => $value];
            });

        $timestamp = $signature_parts->get('ts');
        $received_hash = $signature_parts->get('v1');

        if (blank($timestamp) || blank($received_hash)) {
            return false;
        }

        $data_id = $this->query_data_id($request);
        $request_id = $request->header('x-request-id');

        $manifest = collect([
            'id' => filled($data_id) ? strtolower($data_id) : null,
            'request-id' => $request_id,
            'ts' => $timestamp,
        ])
            ->filter(fn (?string $value): bool => filled($value))
            ->map(fn (string $value, string $key): string => $key.':'.$value.';')
            ->implode('');

        return hash_equals(hash_hmac('sha256', $manifest, $secret), $received_hash);
    }

    public function webhook_payment_id(Request $request): ?string
    {
        $payment_id = $this->query_data_id($request) ?? $request->input('data.id');

        return filled($payment_id) ? (string) $payment_id : null;
    }

    public function find_payment(string $payment_id): array
    {
        $response = $this->api()->get(self::API_URL.'/v1/payments/'.rawurlencode($payment_id));

        if ($response->failed()) {
            throw new RuntimeException('Falha ao consultar pagamento no Mercado Pago: HTTP '.$response->status());
        }

        return $response->json();
    }

    public function payment_matches_order(array $payment, Order $order): bool
    {
        return $order->payment_provider === 'mercado_pago'
            && ($payment['currency_id'] ?? null) === 'BRL'
            && isset($payment['transaction_amount'])
            && $this->to_cents($payment['transaction_amount']) === $this->to_cents($order->total_amount);
    }

    private function api(): PendingRequest
    {
        $access_token = config('fotx.mercado_pago_access_token');

        if (blank($access_token)) {
            throw new RuntimeException('MERCADO_PAGO_ACCESS_TOKEN não configurado.');
        }

        return Http::withToken($access_token)
            ->acceptJson()
            ->timeout(15)
            ->withHeaders(array_filter([
                'X-Integrator-Id' => config('fotx.mercado_pago_integrator_id'),
            ]));
    }

    private function checkout_item_title(Order $order): string
    {
        $photos_count = $order->items->count();

        return sprintf('Fotos do evento %s (%d %s)', $order->event->name, $photos_count, $photos_count === 1 ? 'foto' : 'fotos');
    }

    // O PHP converte "data.id" da query em "data_id"; parseQuery preserva o nome original.
    private function query_data_id(Request $request): ?string
    {
        $data_id = HeaderUtils::parseQuery((string) $request->getQueryString())['data.id'] ?? null;

        return filled($data_id) ? (string) $data_id : null;
    }

    private function to_cents(string|int|float $amount): int
    {
        return (int) str_replace('.', '', number_format((float) $amount, 2, '.', ''));
    }
}
