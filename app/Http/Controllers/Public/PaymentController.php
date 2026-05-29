<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\EventAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function approve_mock(Order $order, string $download_token, EventAnalyticsService $analytics): RedirectResponse
    {
        $this->authorize_token($order, $download_token);
        abort_unless($order->payment_provider === 'mock', 403);

        $was_pending = $order->status !== 'paid';
        $order->mark_as_paid($order->payment_reference);

        if ($was_pending) {
            $analytics->record(
                event: $order->event,
                type: 'paid_order',
                source: 'mock',
                order: $order,
                metadata: [
                    'total_amount' => (float) $order->total_amount,
                    'items_count' => $order->items()->count(),
                ],
            );
        }

        return redirect()->route('orders.success', [$order, $download_token]);
    }

    public function mercado_pago_webhook(Request $request): Response
    {
        // Produção: validar assinatura do Mercado Pago, buscar o pagamento
        // pelo ID recebido e aprovar o Order via external_reference.
        report(new \RuntimeException('Webhook Mercado Pago recebido em modo placeholder: '.$request->getContent()));

        return response(status: 200);
    }

    private function authorize_token(Order $order, string $download_token): void
    {
        abort_unless(hash_equals((string) $order->download_token, $download_token), 403);
    }
}
