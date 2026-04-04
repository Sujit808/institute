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

    'attendance_integration' => [
        'webhook_token' => env('ATTENDANCE_WEBHOOK_TOKEN'),
    ],

    'billing' => [
        'provider' => env('BILLING_PROVIDER', 'generic'),
        'webhook_secret' => env('BILLING_WEBHOOK_SECRET'),
        'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'razorpay_webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('BILLING_WEBHOOK_TOLERANCE', 300),
        'fire_allowed_hosts' => array_values(array_filter(array_map(
            static fn ($host) => strtolower(trim((string) $host)),
            explode(',', (string) env('BILLING_FIRE_ALLOWED_HOSTS', 'localhost,127.0.0.1,::1'))
        ))),
        'default_currency' => env('BILLING_DEFAULT_CURRENCY', 'INR'),
    ],

];
