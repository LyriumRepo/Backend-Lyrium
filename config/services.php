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

    'culqi' => [
        'public_key' => env('CULQI_PUBLIC_KEY'),
        'secret_key' => env('CULQI_SECRET_KEY'),
        'mode' => env('CULQI_MODE', 'test'),
        'webhook_public_key' => env('CULQI_WEBHOOK_PUBLIC_KEY'),
    ],

    'izipay' => [
        // Credenciales de autenticación (Basic Auth)
        'user_id'     => env('IZIPAY_USER_ID', env('IZIPAY_USERNAME', '')),
        'password'    => env('IZIPAY_PASSWORD', ''),
        // Modo de operación
        'mode'        => env('IZIPAY_MODE', 'test'),
        // Llave para verificación de hash (webhook)
        'hash_key'    => env('IZIPAY_HASH_KEY', ''),
        // Modo simulado (HEAD legacy)
        'mock'        => env('IZIPAY_MOCK', true),
        'api_url'     => env('IZIPAY_API_URL', 'https://api.micuentaweb.pe/api-payment'),
        'public_key'  => env('IZIPAY_PUBLIC_KEY', ''),
        'private_key' => env('IZIPAY_PRIVATE_KEY', ''),
        'username'    => env('IZIPAY_USERNAME', env('IZIPAY_USER_ID', '')),
        'hmac_key'    => env('IZIPAY_HMAC_KEY', ''),
        'shop_id'     => env('IZIPAY_SHOP_ID', ''),
    ],

    'miapicloud' => [
        'token' => env('MIAPICLOUD', env('MIAPICLOUD_TOKEN', '')),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Callback dinámico mapeado a la URI de tu controlador
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', 'http://127.0.0.1:8000/api/google/callback'),
    ],

    // ─── GOOGLE SERVICE ACCOUNT (Soporte Fallback / Plan B) ──────────────────
    'google_calendar' => [
        'credentials_path' => env('GOOGLE_CALENDAR_CREDENTIALS_PATH', storage_path('app/google-service-account.json')),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'fcm' => [
        'project_id'       => env('FCM_PROJECT_ID'),
        'credentials_json' => env('FCM_CREDENTIALS_JSON'),     // base64 del JSON de cuenta de servicio
        'credentials_path' => env('FCM_CREDENTIALS_PATH', storage_path('app/fcm-service-account.json')),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'nubefact' => [
        'route' => env('NUBEFACT_ROUTE', 'https://api.nubefact.com/api/v1/06259bc7-b074-43bd-8981-53fa863f787f'),
        'token' => env('NUBEFACT_TOKEN', 'a4e2903adce14230ac2f744c2e785b6681ad211178484c498faf0740aeeac05e'),
        'ruc' => env('NUBEFACT_RUC', '20600695771'),
        'branch_id' => env('NUBEFACT_BRANCH_ID', '0'),
        'timeout' => env('NUBEFACT_TIMEOUT', 30),
        'connect_timeout' => env('NUBEFACT_CONNECT_TIMEOUT', 10),
        'series' => [
            'FACTURA' => 'FFF1',
            'BOLETA' => 'BBB1',
            'NOTA_CREDITO' => 'FFF1',
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


];

