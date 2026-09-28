<?php

return [
    'face_selfie_ttl_hours' => (int) env('FACE_SELFIE_TTL_HOURS', 24),
    'face_search_max_attempts' => (int) env('FACE_SEARCH_MAX_ATTEMPTS', 5),
    'face_search_decay_minutes' => (int) env('FACE_SEARCH_DECAY_MINUTES', 10),
    'face_recognition_driver' => env('FACE_RECOGNITION_DRIVER', 'mock'),
    'face_match_threshold' => (float) env('FACE_MATCH_THRESHOLD', 90),
    'face_search_max_results' => (int) env('FACE_SEARCH_MAX_RESULTS', 50),
    'rekognition_region' => env('REKOGNITION_REGION', 'sa-east-1'),
    'rekognition_access_key_id' => env('REKOGNITION_ACCESS_KEY_ID'),
    'rekognition_secret_access_key' => env('REKOGNITION_SECRET_ACCESS_KEY'),
    'rekognition_collection_prefix' => env('REKOGNITION_COLLECTION_PREFIX') ?: 'fotx-'.env('APP_ENV', 'production'),
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
