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

    /*
    |--------------------------------------------------------------------------
    | Plesk Configuration
    |--------------------------------------------------------------------------
    */

    'plesk' => [
        'host' => env('PLESK_HOST'),
        'username' => env('PLESK_USERNAME'),
        'password' => env('PLESK_PASSWORD'),
        'port' => env('PLESK_PORT', '8443'),
        'protocol' => env('PLESK_PROTOCOL', 'https'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenProvider Configuration
    |--------------------------------------------------------------------------
    */

    'openprovider' => [
        'api_url' => env('OPENPROVIDER_API_URL', 'https://api.openprovider.eu'),
        'username' => env('OPENPROVIDER_USERNAME'),
        'password' => env('OPENPROVIDER_PASSWORD'),
        'api_token' => env('OPENPROVIDER_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Moneybird Configuration
    |--------------------------------------------------------------------------
    */

    'moneybird' => [
        'api_url' => env('MONEYBIRD_API_URL', 'https://moneybird.com/api/v2'),
        'api_token' => env('MONEYBIRD_API_TOKEN'),
        'administration_id' => env('MONEYBIRD_ADMINISTRATION_ID'),
        'default_tax_rate_id' => env('MONEYBIRD_DEFAULT_TAX_RATE_ID', '1'),
        'default_financial_account_id' => env('MONEYBIRD_DEFAULT_FINANCIAL_ACCOUNT_ID', '1'),
    ],

];
