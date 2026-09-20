<?php

use Laravel\Sanctum\Sanctum;

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
            ? ','.parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)
            : ''
    ))),

    'guard' => ['web'],

    // The Flutter app authenticates with a bearer token (no cookies), so tokens
    // effectively never expire unless you set this — revoke via logout instead.
    'expiration' => env('SANCTUM_TOKEN_EXPIRATION_MINUTES'),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
