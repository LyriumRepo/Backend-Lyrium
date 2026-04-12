<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(['slug' => 'emprende'], [
            'name' => 'EMPRENDE',
            'monthly_fee' => 0,
            'commission_rate' => 0.0500,
            'has_membership_fee' => false,
            'features' => [
                'Exposición de productos al buscar en LYRIUM',
                'Espacio propio para personalizar tu tienda',
                'Gestión de pedidos y inventario básico',
                'Soporte técnico por email',
            ],
            'detailed_benefits' => [
                [
                    'title' => 'Exposición de productos',
                    'description' => 'Tus productos aparecerán en los resultados de búsqueda de LYRIUM',
                    'icon' => '🔍',
                ],
                [
                    'title' => 'Tienda personalizada',
                    'description' => 'Espacio propio para configurar tu tienda virtual con tu identidad de marca',
                    'icon' => '🎨',
                ],
                [
                    'title' => 'Gestión de pedidos',
                    'description' => 'Administra tus pedidos e inventario de manera eficiente',
                    'icon' => '📦',
                ],
                [
                    'title' => 'Soporte técnico',
                    'description' => 'Atención técnica por email para resolver tus dudas',
                    'icon' => '📧',
                ],
            ],
        ]);

        Plan::updateOrCreate(['slug' => 'crece'], [
            'name' => 'CRECE',
            'monthly_fee' => 7.99,
            'commission_rate' => 0.1000,
            'has_membership_fee' => true,
            'features' => [
                'Todo lo del plan EMPRENDE',
                'Logo en banners principales de LYRIUM',
                'Capacitaciones online en postventa',
                'Medalla de producto recomendado por LYRIUM',
                'Atención preferencial por LYRIUM',
            ],
            'detailed_benefits' => [
                [
                    'title' => 'Logo en banners principales',
                    'description' => 'Tu logotipo se exhibirá en los banners principales de LYRIUM',
                    'icon' => '🏆',
                ],
                [
                    'title' => 'Capacitaciones online',
                    'description' => 'Accede a sesiones de capacitación online en postventa',
                    'icon' => '🎓',
                ],
                [
                    'title' => 'Producto recomendado',
                    'description' => 'Recibe una insignia de producto recomendado por LYRIUM',
                    'icon' => '⭐',
                ],
                [
                    'title' => 'Atención preferencial',
                    'description' => 'Soporte prioritario para resolver tus consultas más rápido',
                    'icon' => '💬',
                ],
            ],
        ]);

        Plan::updateOrCreate(['slug' => 'especial'], [
            'name' => 'ESPECIAL',
            'monthly_fee' => 89.90,
            'commission_rate' => 0.1500,
            'has_membership_fee' => true,
            'features' => [
                'Todo lo del plan CRECE',
                'Banner personalizado en homepage de LYRIUM',
                'Servicio de atención al cliente personalizado',
                'Análisis de mercado y competidores',
                'Prioridad en búsquedas y filtros',
            ],
            'detailed_benefits' => [
                [
                    'title' => 'Banner personalizado',
                    'description' => 'Tu banner personalizado aparecerá en el homepage de LYRIUM',
                    'icon' => '🖼️',
                ],
                [
                    'title' => 'Atención al cliente personalizada',
                    'description' => 'Servicio dedicado de atención al cliente para ti',
                    'icon' => '👤',
                ],
                [
                    'title' => 'Análisis de mercado',
                    'description' => 'Reports y análisis de mercado y competidores',
                    'icon' => '📊',
                ],
                [
                    'title' => 'Prioridad en búsquedas',
                    'description' => 'Tus productos aparecerán primero en búsquedas y filtros',
                    'icon' => '🔝',
                ],
            ],
        ]);
    }
}
