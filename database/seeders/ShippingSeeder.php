<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

final class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'standard',
                'name' => 'Envío Estándar',
                'description' => 'Entrega en 3-5 días hábiles',
                'type' => 'standard',
                'base_cost' => 15.00,
                'free_shipping_min' => 150.00,
                'estimated_days' => 5,
                'allows_tracking' => true,
                'is_active' => true,
            ],
            [
                'code' => 'express',
                'name' => 'Envío Express',
                'description' => 'Entrega en 24-48 horas',
                'type' => 'express',
                'base_cost' => 35.00,
                'free_shipping_min' => null,
                'estimated_days' => 2,
                'allows_tracking' => true,
                'is_active' => true,
            ],
            [
                'code' => 'overnight',
                'name' => 'Envío Overnight',
                'description' => 'Entrega al día siguiente',
                'type' => 'overnight',
                'base_cost' => 60.00,
                'free_shipping_min' => null,
                'estimated_days' => 1,
                'allows_tracking' => true,
                'is_active' => true,
            ],
            [
                'code' => 'pickup',
                'name' => 'Retiro en Tienda',
                'description' => 'Retira en nuestra tienda',
                'type' => 'pickup',
                'base_cost' => 0.00,
                'free_shipping_min' => null,
                'estimated_days' => 0,
                'allows_tracking' => false,
                'is_active' => true,
            ],
            [
                'code' => 'free',
                'name' => 'Envío Gratis',
                'description' => 'Envío gratuito para pedidos especiales',
                'type' => 'free',
                'base_cost' => 0.00,
                'free_shipping_min' => 300.00,
                'estimated_days' => 5,
                'allows_tracking' => true,
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }

        $zones = [
            ['name' => 'Lima Metropolitana', 'country' => 'PE', 'region' => 'Lima', 'department' => 'Lima'],
            ['name' => 'Lima Provincias', 'country' => 'PE', 'region' => 'Lima', 'department' => 'Lima'],
            ['name' => 'Arequipa', 'country' => 'PE', 'region' => 'Arequipa', 'department' => 'Arequipa'],
            ['name' => 'Trujillo', 'country' => 'PE', 'region' => 'La Libertad', 'department' => 'La Libertad'],
            ['name' => 'Cusco', 'country' => 'PE', 'region' => 'Cusco', 'department' => 'Cusco'],
            ['name' => 'Resto del Perú', 'country' => 'PE', 'region' => 'OTHER', 'department' => null],
        ];

        foreach ($zones as $zone) {
            ShippingZone::updateOrCreate(
                ['name' => $zone['name']],
                [
                    'name' => $zone['name'],
                    'country' => $zone['country'],
                    'region' => $zone['region'],
                    'department' => $zone['department'],
                    'min_weight' => 0,
                    'max_weight' => 100,
                    'is_active' => true,
                ]
            );
        }

        $standardMethod = ShippingMethod::where('code', 'standard')->first();
        $zones = ShippingZone::all();
        $baseCosts = [
            'Lima Metropolitana' => 10.00,
            'Lima Provincias' => 20.00,
            'Arequipa' => 25.00,
            'Trujillo' => 25.00,
            'Cusco' => 30.00,
            'Resto del Perú' => 40.00,
        ];

        foreach ($zones as $zone) {
            ShippingRate::updateOrCreate(
                [
                    'shipping_method_id' => $standardMethod->id,
                    'zone_id' => $zone->id,
                ],
                [
                    'shipping_method_id' => $standardMethod->id,
                    'zone_id' => $zone->id,
                    'weight_from' => 0,
                    'weight_to' => 100,
                    'price' => $baseCosts[$zone->name] ?? 10.00,
                    'estimated_days' => $standardMethod->estimated_days,
                    'is_active' => true,
                ]
            );
        }
    }
}
