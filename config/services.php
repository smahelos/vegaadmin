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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gopay' => [
        'enabled' => env('GOPAY_ENABLED', false),
        'goid' => env('GOPAY_GOID'),
        'client_id' => env('GOPAY_CLIENT_ID'),
        'client_secret' => env('GOPAY_CLIENT_SECRET'),
        'production' => env('GOPAY_PRODUCTION', false),
        'gateway_url' => env('GOPAY_GATEWAY_URL', 'https://gw.sandbox.gopay.com/'),
        'test_return_url' => env('GOPAY_TEST_RETURN_URL', 'https://postman-echo.com/get'),
        'test_notify_url' => env('GOPAY_TEST_NOTIFY_URL', 'https://postman-echo.com/post'),
    ],

];
