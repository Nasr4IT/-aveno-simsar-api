<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    // Sham Cash — powers the paid featured-ad packages (15/30/60 days).
    // See app/Services/ShamCash/ShamCashClient.php and
    // App\Http\Controllers\Api\PaymentController.
    'shamcash' => [
        'base_url' => env('SHAMCASH_BASE_URL'),
        'merchant_id' => env('SHAMCASH_MERCHANT_ID'),
        'api_key' => env('SHAMCASH_API_KEY'),
        'api_secret' => env('SHAMCASH_API_SECRET'),
        'webhook_secret' => env('SHAMCASH_WEBHOOK_SECRET'),
        'callback_url' => env('SHAMCASH_CALLBACK_URL'),
    ],

    'firebase' => [
        // Phase 2 — push notifications (see proposal, "الخدمات المستقبلية").
        'server_key' => env('FIREBASE_SERVER_KEY'),
    ],
];
