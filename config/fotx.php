<?php

return [
    'face_selfie_ttl_hours' => (int) env('FACE_SELFIE_TTL_HOURS', 24),
    'face_search_max_attempts' => (int) env('FACE_SEARCH_MAX_ATTEMPTS', 5),
    'face_search_decay_minutes' => (int) env('FACE_SEARCH_DECAY_MINUTES', 10),
    'payment_gateway' => env('PAYMENT_GATEWAY', 'mock'),
    'mercado_pago_access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
    'mercado_pago_public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
    'mercado_pago_integrator_id' => env('MERCADO_PAGO_INTEGRATOR_ID'),
];
