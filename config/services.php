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
        'public_key'        => env('CULQI_PUBLIC_KEY'),
        'secret_key'        => env('CULQI_SECRET_KEY'),
        'mode'              => env('CULQI_MODE', 'test'),
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

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Callback dinámico mapeado a la URI de tu controlador
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI', 'http://127.0.0.1:8000/api/google/callback'),
    ],

    // ─── GOOGLE SERVICE ACCOUNT (Soporte Fallback / Plan B) ──────────────────
    'google_calendar' => [
        'credentials_path' => env('GOOGLE_CALENDAR_CREDENTIALS_PATH', storage_path('app/google-service-account.json')),
        'calendar_id'      => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'api_url'    => 'https://fcm.googleapis.com/fcm/send',
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

    'nubefact' => [
        /*
        |--------------------------------------------------------------------------
        | NubeFact — Facturación Electrónica SUNAT (Perú)
        |--------------------------------------------------------------------------
        |
        | NubeFact es el proveedor de facturación electrónica que permite emitir
        | comprobantes (Factura, Boleta, Nota de Crédito) con validación SUNAT.
        |
        | URL de la API de NubeFact (producción o testing):
        |   NUBEFACT_URL = https://api.nubefact.com/api/v1
        |
        | Token de acceso (generado desde el panel de NubeFact):
        |   NUBEFACT_TOKEN = tu_token_api
        |
        | Timeouts configurables para las peticiones HTTP.
        */
        'url' => env('NUBEFACT_URL'),
        'token' => env('NUBEFACT_TOKEN'),
        'timeout' => env('NUBEFACT_TIMEOUT', 30),
        'connect_timeout' => env('NUBEFACT_CONNECT_TIMEOUT', 10),

        /*
        |--------------------------------------------------------------------------
        | Series de comprobantes registradas en NubeFact
        |--------------------------------------------------------------------------
        |
        | Mapa de series según el tipo de comprobante.
        | Debe coincidir con lo configurado en NubeFact > Locales y series.
        */
        'series' => [
            'FACTURA' => 'FFF1',
            'BOLETA' => 'BBB1',
            'NOTA_CREDITO' => 'FFF1',
        ],
    ],
];

