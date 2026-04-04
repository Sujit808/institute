<?php

return [
    'access' => [
        // Set true to block all non-local / non-whitelisted IPs.
        'block_external' => env('SECURITY_BLOCK_EXTERNAL', false),
        'allow_localhost' => env('SECURITY_ALLOW_LOCALHOST', true),
        'allowed_ips' => array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            explode(',', (string) env('SECURITY_ALLOWED_IPS', '127.0.0.1,::1'))
        ))),
    ],

    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', false),
        ],

        // Keep empty by default because this project uses inline scripts and CDN assets.
        // If you set this, provide a full CSP policy string.
        'content_security_policy' => env('SECURITY_CSP', ''),
    ],

    'login' => [
        // Brute-force protection controls.
        'max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('SECURITY_LOGIN_DECAY_MINUTES', 10),
    ],

    'force_https' => env('SECURITY_FORCE_HTTPS', false),
];
