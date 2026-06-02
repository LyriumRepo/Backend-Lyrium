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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
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

    /*
    |--------------------------------------------------------------------------
    | Izipay — Pasarela de Pago (Mi Cuenta Web)
    |--------------------------------------------------------------------------
    |
    | Izipay es la pasarela de pagos para procesar tarjetas de crédito/débito.
    | Si no se configuran las credenciales, el sistema opera en modo MOCK
    | para permitir pruebas del flujo de facturación sin conexión real.
    |
    | IZIPAY_MOCK=true  → usa el modo simulado (sin conexión real)
    | IZIPAY_MOCK=false → usa la API REST real de Izipay
    |
    | Credenciales: https://micuentaweb.pe
    */
    'izipay' => [
        'mock'      => env('IZIPAY_MOCK', true),
        'api_url'   => env('IZIPAY_API_URL', 'https://api.micuentaweb.pe/api-payment'),
        'public_key' => env('IZIPAY_PUBLIC_KEY', ''),
        'private_key' => env('IZIPAY_PRIVATE_KEY', ''),
        'username'  => env('IZIPAY_USERNAME', ''),
        'password'  => env('IZIPAY_PASSWORD', ''),
        'hmac_key'  => env('IZIPAY_HMAC_KEY', ''),
        'shop_id'   => env('IZIPAY_SHOP_ID', ''),
    ],

];

