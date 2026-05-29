<?php

return [
    'face_selfie_ttl_hours' => (int) env('FACE_SELFIE_TTL_HOURS', 24),
    'face_search_max_attempts' => (int) env('FACE_SEARCH_MAX_ATTEMPTS', 5),
    'face_search_decay_minutes' => (int) env('FACE_SEARCH_DECAY_MINUTES', 10),
    'process_photos_sync' => (bool) env('FOTX_PROCESS_PHOTOS_SYNC', true),
    'whatsapp_number' => env('FOTX_WHATSAPP_NUMBER', '5500000000000'),
    'whatsapp_support_message' => env('FOTX_WHATSAPP_SUPPORT_MESSAGE', 'Ola! Preciso de ajuda para encontrar ou comprar minhas fotos no Fotx.'),
    'payment_gateway' => env('PAYMENT_GATEWAY', 'mock'),
    'mercado_pago_access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
    'mercado_pago_public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
    'mercado_pago_integrator_id' => env('MERCADO_PAGO_INTEGRATOR_ID'),
    'cart_volume_discounts' => [
        3 => 0.15,
        5 => 0.20,
        8 => 0.30,
    ],
];
