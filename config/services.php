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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'helloasso' => [
        // sandbox ou production
        'env' => env('HELLOASSO_ENV', 'sandbox'),
        'client_id' => env('HELLOASSO_CLIENT_ID'),
        'client_secret' => env('HELLOASSO_CLIENT_SECRET'),
        'organization_slug' => env('HELLOASSO_ORGANIZATION_SLUG'),
        'base_urls' => [
            'sandbox' => 'https://api.helloasso-sandbox.com',
            'production' => 'https://api.helloasso.com',
        ],
        // Adresses IP d'émission des notifications (facultatif, séparées par des virgules).
        // Documentation HelloAsso : production 51.138.206.200, test 4.233.135.234.
        'webhook_ips' => array_filter(explode(',', (string) env('HELLOASSO_WEBHOOK_IPS', ''))),
        // Délai après lequel un paiement non autorisé est considéré comme abandonné (minutes).
        'abandon_after_minutes' => 120,
    ],

];
