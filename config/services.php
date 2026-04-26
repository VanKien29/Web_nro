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

    'game_api' => [
        'base_url' => env('GAME_API_BASE_URL', ''),
        'topup_secret' => env('TOPUP_SECRET', ''),
    ],

    'game_runtime' => [
        'base_url' => env('GAME_RUNTIME_URL', 'http://127.0.0.1:19091'),
        'key' => env('GAME_RUNTIME_KEY', 'web-admin'),
        'secret' => env('GAME_RUNTIME_SECRET', ''),
        'timeout' => env('GAME_RUNTIME_TIMEOUT', 5),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
        'enabled' => env('RECAPTCHA_ENABLED', false),
    ],

    'sepay' => [
        'token' => env('SEPAY_TOKEN', ''),
        'api_url' => env('SEPAY_API_URL', ''),
        'webhook_api_key' => env('SEPAY_WEBHOOK_API_KEY', ''),
        'cron_secret' => env('SEPAY_CRON_SECRET', ''),
        'prefix' => env('SEPAY_PREFIX', 'naptien'),
    ],

    'thesieutoc' => [
        'api_key' => env('THESIEUTOC_API_KEY', ''),
    ],

];
