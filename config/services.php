<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mada' => [
        'merchant_id' => env('MADA_MERCHANT_ID'),
        'secret_key' => env('MADA_SECRET_KEY'),
        'endpoint' => env('MADA_API_ENDPOINT', 'https://api.mada.local'),
        'callback_url' => env('MADA_CALLBACK_URL'),
        'mock_local' => env('MADA_MOCK_LOCAL', true),
        'auto_complete_local' => env('MADA_AUTO_COMPLETE_LOCAL', true),
    ],

];
