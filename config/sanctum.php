<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1,localhost:8000,127.0.0.1:8000')),
    'expiration' => env('SANCTUM_EXPIRATION'),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    ],
];


