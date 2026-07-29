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
'ariya' => [
    'url' => env('ARIYA_API_URL'),
    'token' => env('ARIYA_API_TOKEN'),
],

'external_sync' => [
    'token' => env('EXTERNAL_SYNC_TOKEN'),
],

'erp' => [
    'enabled' => env('ERP_ENABLED', false),
    'url' => rtrim((string) env('ERP_URL', 'https://inv.ariyajanebi.ir'), '/'),
    'launch_url' => rtrim((string) env('ERP_LAUNCH_URL', 'https://inv.ariyajanebi.ir'), '/'),
    'access_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ERP_ACCESS_ROLES', ''))
    ))),
    'seller_roles' => ['Marketer'],
    'sync_rate_limit' => (int) env('ERP_SYNC_RATE_LIMIT', 60),
    'sync_audit_enabled' => env('ERP_SYNC_AUDIT_ENABLED', false),
],

'legacy_client_token' => [
    'enabled' => env('LEGACY_CLIENT_TOKEN_ENABLED', false),
    'secret' => env('CLIENT_SECRET'),
],

];
