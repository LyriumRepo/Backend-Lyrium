<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Operadores Logísticos Soportados
    |--------------------------------------------------------------------------
    |
    | Cada operador define:
    |   - name        : Nombre comercial visible
    |   - tracking_url: Plantilla de URL de seguimiento ({tracking} se reemplaza)
    |   - fields      : Campos dinámicos que requiere el operador
    |
    | Estructura de cada field:
    |   key      : nombre interno (se guarda en carrier_data)
    |   label    : etiqueta visible en el formulario
    |   type     : tipo de input (text, password)
    |   required : si es obligatorio
    |
    */

    'carriers' => [
        'shalom' => [
            'name' => 'Shalom',
            'tracking_url' => 'https://rastrea.shalom.pe/',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Clave de seguridad', 'type' => 'password', 'required' => true],
                ['key' => 'agency', 'label' => 'Agencia destino', 'type' => 'text', 'required' => false],
            ],
        ],
        'olva' => [
            'name' => 'Olva',
            'tracking_url' => 'https://tracking.olvaexpress.pe/',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Clave de seguridad', 'type' => 'password', 'required' => true],
                ['key' => 'agency', 'label' => 'Agencia destino', 'type' => 'text', 'required' => false],
            ],
        ],
        'urbano' => [
            'name' => 'Urbano',
            'tracking_url' => 'https://www.urbano.com.pe/tracking/',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'agency', 'label' => 'Punto Urbano', 'type' => 'text', 'required' => false],
            ],
        ],
        'sharf' => [
            'name' => 'Sharf',
            'tracking_url' => 'https://envia.holasharf.com/tracking',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'agency', 'label' => 'Punto Sharf', 'type' => 'text', 'required' => false],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lista de códigos válidos (útil para validación in:)
    |--------------------------------------------------------------------------
    */
    'carrier_codes' => ['shalom', 'olva', 'urbano', 'sharf'],
];
