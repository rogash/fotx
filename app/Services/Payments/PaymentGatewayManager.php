<?php

namespace App\Services\Payments;

use InvalidArgumentException;
use RuntimeException;

class PaymentGatewayManager
{
    public function gateway(): PaymentGateway
    {
        return match (config('fotx.payment_gateway', 'mock')) {
            'mock' => app()->isProduction()
                ? throw new RuntimeException('Gateway de pagamento mock não pode ser usado em produção.')
                : app(MockPaymentGateway::class),
            'mercado_pago' => app(MercadoPagoPaymentGateway::class),
            default => throw new InvalidArgumentException('Gateway de pagamento invalido.'),
        };
    }
}
