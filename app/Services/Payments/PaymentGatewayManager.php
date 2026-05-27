<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentGatewayManager
{
    public function gateway(): PaymentGateway
    {
        return match (config('fotx.payment_gateway', 'mock')) {
            'mock' => app(MockPaymentGateway::class),
            'mercado_pago' => app(MercadoPagoPaymentGateway::class),
            default => throw new InvalidArgumentException('Gateway de pagamento invalido.'),
        };
    }
}
