<?php

namespace App\Services\Payments;

final readonly class PaymentCheckoutData
{
    public function __construct(
        public string $provider,
        public string $reference,
        public string $checkout_url,
    ) {}
}
