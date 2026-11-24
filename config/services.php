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

    // Push notifications via FCM HTTP v1 (see App\Services\PushNotificationService
    // and docs/HOW_IT_WORKS.md § Push Notifications). Needs a Firebase service
    // account — FCM's older server-key API this used to target is retired, so
    // that's the only live option. credentials_json is the full service
    // account JSON (Firebase console ▸ Project settings ▸ Service accounts ▸
    // Generate new private key), either as a single-line string env value
    // (FIREBASE_CREDENTIALS_JSON) or — easier on a server — as the downloaded
    // file itself, uploaded and referenced by FIREBASE_CREDENTIALS_PATH
    // (absolute, or relative to the project folder). The JSON value wins if
    // both are set.
    'firebase' => [
        'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),
    ],
];
