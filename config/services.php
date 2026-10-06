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

    // Push notifications via FCM HTTP v1 (see App\Services\PushNotificationService
    // and docs/HOW_IT_WORKS.md § Push Notifications). Needs a Firebase service
    // account — FCM's older server-key API this used to target is retired, so
    // that's the only live option. credentials_json is the full service
    // account JSON (Firebase console ▸ Project settings ▸ Service accounts ▸
    // Generate new private key), as a single-line string env value.
    'firebase' => [
        'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),
    ],
];
