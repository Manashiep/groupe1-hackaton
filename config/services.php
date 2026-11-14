<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | SimuPay : fausse passerelle de paiement mobile (simulation, aucun vrai argent).
    | Elle tourne comme un service séparé (php artisan serve --port=8001).
    */
    'simupay' => [
        'url' => env('SIMUPAY_URL', 'http://127.0.0.1:8001/simupay'),
        'secret_key' => env('SIMUPAY_SECRET_KEY'),       // CleanComores → SimuPay : « c'est bien moi »
        'webhook_secret' => env('SIMUPAY_WEBHOOK_SECRET'), // SimuPay → CleanComores : signature des confirmations
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
