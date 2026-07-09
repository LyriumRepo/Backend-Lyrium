<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con la API oficial de Cloudflare v4.
    | Todos los valores sensibles se obtienen desde variables de entorno.
    |
    | Documentación: https://developers.cloudflare.com/api/
    |
    */

    'base_url' => env('CLOUDFLARE_BASE_URL', 'https://api.cloudflare.com/client/v4'),

    'api_token' => env('CLOUDFLARE_API_TOKEN'),

    'zone_id' => env('CLOUDFLARE_ZONE_ID'),

    'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */

    'timeout' => env('CLOUDFLARE_TIMEOUT', 15),

    'retry_times' => env('CLOUDFLARE_RETRY_TIMES', 3),

    'retry_sleep_ms' => env('CLOUDFLARE_RETRY_SLEEP_MS', 500),

    'verify_ssl' => env('CLOUDFLARE_VERIFY_SSL', true),

];
