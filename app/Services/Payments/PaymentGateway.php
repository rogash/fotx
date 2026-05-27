<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    public function create_checkout(Order $order): PaymentCheckoutData;
}
