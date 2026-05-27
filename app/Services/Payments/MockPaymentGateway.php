<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGateway
{
    public function create_checkout(Order $order): PaymentCheckoutData
    {
        return new PaymentCheckoutData(
            provider: 'mock',
            reference: 'MOCK-PREF-'.Str::upper(Str::random(10)),
            checkout_url: route('payments.mock.approve', [$order, $order->download_token]),
        );
    }
}
