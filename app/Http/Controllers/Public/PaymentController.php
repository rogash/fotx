<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\EventAnalyticsService;
use App\Services\Payments\MercadoPagoPaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function approve_mock(Order $order, string $download_token, EventAnalyticsService $analytics): RedirectResponse
    {
        abort_if(app()->isProduction() || config('fotx.payment_gateway') !== 'mock', 404);
        $this->authorize_token($order, $download_token);
        abort_unless($order->payment_provider === 'mock', 403);

        $this->confirm_payment($order, $order->payment_reference, 'mock', $analytics);

        return redirect()->route('orders.success', [$order, $download_token]);
    }

    public function mercado_pago_webhook(Request $request, MercadoPagoPaymentGateway $gateway, EventAnalyticsService $analytics): Response
    {
        if (! $gateway->has_valid_webhook_signature($request)) {
            return response(status: 401);
        }

        $payment_id = $gateway->webhook_payment_id($request);

        if ($request->query('type', $request->input('type')) !== 'payment' || $payment_id === null) {
            return response(status: 200);
        }

        // O corpo da notificação não é confiável: o status vem sempre da API.
        $payment = $gateway->find_payment($payment_id);

        if (($payment['status'] ?? null) !== 'approved') {
            return response(status: 200);
        }

        $order = Order::query()->where('public_id', (string) ($payment['external_reference'] ?? ''))->first();

        if (! $order || ! $gateway->payment_matches_order($payment, $order)) {
            Log::warning('Pagamento Mercado Pago aprovado sem pedido correspondente.', [
                'payment_id' => $payment_id,
                'order_id' => $order?->id,
            ]);

            return response(status: 200);
        }

        $this->confirm_payment($order, (string) $payment['id'], 'mercado_pago', $analytics);

        return response(status: 200);
    }

    // Idempotente: notificações repetidas não reaprovam o pedido nem duplicam métricas.
    private function confirm_payment(Order $order, ?string $payment_reference, string $source, EventAnalyticsService $analytics): void
    {
        DB::transaction(function () use ($order, $payment_reference, $source, $analytics): void {
            $locked_order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked_order->status === 'paid') {
                return;
            }

            $locked_order->mark_as_paid($payment_reference);

            $analytics->record(
                event: $locked_order->event,
                type: 'paid_order',
                source: $source,
                order: $locked_order,
                metadata: [
                    'total_amount' => (float) $locked_order->total_amount,
                    'items_count' => $locked_order->items()->count(),
                ],
            );
        });
    }

    private function authorize_token(Order $order, string $download_token): void
    {
        abort_unless(hash_equals((string) $order->download_token, $download_token), 403);
    }
}
