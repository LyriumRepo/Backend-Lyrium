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
        'user_id'  => env('IZIPAY_USER_ID'),
        'password' => env('IZIPAY_PASSWORD'),
        'mode'     => env('IZIPAY_MODE', 'test'),
        'hash_key' => env('IZIPAY_HASH_KEY', ''),
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

<<<<<<< HEAD

=======
    'rapifac' => [
        /*
        |--------------------------------------------------------------------------
        | Rapifac — Facturación Electrónica SUNAT (Perú)
        |--------------------------------------------------------------------------
        |
        | Rapifac es el proveedor de facturación electrónica que permite emitir
        | comprobantes (Factura, Boleta, Nota de Crédito) con validación SUNAT.
        |
        | URLs de API según entorno:
        |   Testing:
        |     RAPIFAC_AUTH_URL = https://wsoauth-p1.rapifac.com/oauth2/token
        |     RAPIFAC_SALES_URL = https://wsventas-p1.rapifac.com/v0/comprobantes
        |     RAPIFAC_PDF_URL = https://wsventas-p1.rapifac.com/v0/comprobantes
        |   Producción:
        |     RAPIFAC_AUTH_URL = https://wsoauth.rapifac.com/oauth2/token
        |     RAPIFAC_SALES_URL = https://wsventas.rapifac.com/v0/comprobantes
        |     RAPIFAC_PDF_URL = https://wsventas.rapifac.com/v0/comprobantes
        |
        | Credenciales se obtienen del panel de Rapifac:
        |   - RUC: RUC de la empresa registrada en Rapifac
        |   - Usuario/Contraseña: credenciales del panel Rapifac
        |   - Branch ID: código de local/sucursal (opcional, default 1)
        |
        | Tiempo de vida del token: ~1 hora. El servicio lo cachea y renueva
        | automáticamente al expirar (refresh automático en 401).
        */
        'auth_url' => env('RAPIFAC_AUTH_URL'),
        'sales_url' => env('RAPIFAC_SALES_URL'),
        'ruc' => env('RAPIFAC_RUC'),
        'user' => env('RAPIFAC_USER'),
        'password' => env('RAPIFAC_PASSWORD'),
        'branch_id' => env('RAPIFAC_BRANCH_ID'),

        /*
        | URL base para descarga de PDF de comprobantes.
        | Normalmente coincide con RAPIFAC_SALES_URL.
        | Si Rapifac proporciona una URL diferente para PDFs, configúrala aquí.
        */
        'pdf_url' => env('RAPIFAC_PDF_URL'),

        /*
        | Timeouts y reintentos para peticiones HTTP a Rapifac.
        | Ajusta según la latencia de tu conexión a los servidores de Rapifac.
        */
        'timeout' => env('RAPIFAC_TIMEOUT', 30),
        'connect_timeout' => env('RAPIFAC_CONNECT_TIMEOUT', 10),
        'retry_attempts' => env('RAPIFAC_RETRY_ATTEMPTS', 3),
    ],
>>>>>>> 49185e4 (cambios recientes en backent para PreMain)

];
