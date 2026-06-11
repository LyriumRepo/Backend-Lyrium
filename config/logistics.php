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
            'tracking_url' => 'https://shalomcourier.com/track?code={tracking}',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'pickup_code', 'label' => 'Código de retiro', 'type' => 'text', 'required' => false],
                ['key' => 'agency', 'label' => 'Agencia destino', 'type' => 'text', 'required' => false],
            ],
        ],
        'olva' => [
            'name' => 'Olva',
            'tracking_url' => 'https://www.olvacourier.com/track?codigo={tracking}',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'agency', 'label' => 'Agencia destino', 'type' => 'text', 'required' => false],
            ],
        ],
        'urbano' => [
            'name' => 'Urbano',
            'tracking_url' => 'https://urbanocourier.com/tracking?n={tracking}',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'required' => false],
            ],
        ],
        'sharf' => [
            'name' => 'Sharf',
            'tracking_url' => 'https://sharfexpress.com/tracking?cod={tracking}',
            'fields' => [
                ['key' => 'tracking_code', 'label' => 'Código de seguimiento', 'type' => 'text', 'required' => true],
                ['key' => 'pickup_code', 'label' => 'Código de retiro', 'type' => 'text', 'required' => false],
                ['key' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'required' => false],
                ['key' => 'agency', 'label' => 'Agencia destino', 'type' => 'text', 'required' => false],
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
