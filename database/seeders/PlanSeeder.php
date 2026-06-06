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
            'is_active' => true,
            'timeline_icon' => 'star',
            'badge' => null,
            'description' => 'Perfecto para empezar a vender en LYRIUM sin costo inicial.',
            'css_color' => '#10b981',
            'accent_color' => '#059669',
            'requires_payment' => false,
            'enable_claim_lock' => false,
            'claim_months' => 1,
            'subscribe_button_text' => 'Comenzar gratis',
            'currency' => 'S/',
            'period' => '/mes',
            'price_annual' => null,
            'price_text' => 'Gratis',
            'price_subtext' => 'Siempre gratis',
            'use_price_mode' => false,
            'compact_visible_count' => 4,
            'features' => [
                ['text' => 'Exposición de productos al buscar en LYRIUM', 'active' => true],
                ['text' => 'Espacio propio para personalizar tu tienda', 'active' => true],
                ['text' => 'Gestión de pedidos y inventario básico', 'active' => true],
                ['text' => 'Soporte técnico por email', 'active' => true],
            ],
            'detailed_benefits' => [
                ['emoji' => '🔍', 'title' => 'Exposición de productos', 'description' => 'Tus productos aparecerán en los resultados de búsqueda de LYRIUM', 'color' => '#10b981'],
                ['emoji' => '🎨', 'title' => 'Tienda personalizada', 'description' => 'Espacio propio para configurar tu tienda virtual con tu identidad de marca', 'color' => '#10b981'],
                ['emoji' => '📦', 'title' => 'Gestión de pedidos', 'description' => 'Administra tus pedidos e inventario de manera eficiente', 'color' => '#10b981'],
                ['emoji' => '📧', 'title' => 'Soporte técnico', 'description' => 'Atención técnica por email para resolver tus dudas', 'color' => '#10b981'],
            ],
        ]);

        Plan::updateOrCreate(['slug' => 'crece'], [
            'name' => 'CRECE',
            'monthly_fee' => 7.99,
            'commission_rate' => 0.1000,
            'has_membership_fee' => true,
            'is_active' => true,
            'timeline_icon' => 'trending-up',
            'badge' => 'Popular',
            'description' => 'El plan ideal para hacer crecer tu negocio con herramientas avanzadas.',
            'css_color' => '#3b82f6',
            'accent_color' => '#2563eb',
            'requires_payment' => true,
            'enable_claim_lock' => true,
            'claim_months' => 3,
            'subscribe_button_text' => 'Suscribirse',
            'currency' => 'S/',
            'period' => '/mes',
            'price_annual' => 79.90,
            'price_text' => null,
            'price_subtext' => '/mes',
            'use_price_mode' => true,
            'compact_visible_count' => 5,
            'features' => [
                ['text' => 'Todo lo del plan EMPRENDE', 'active' => true],
                ['text' => 'Logo en banners principales de LYRIUM', 'active' => true],
                ['text' => 'Capacitaciones online en postventa', 'active' => true],
                ['text' => 'Medalla de producto recomendado por LYRIUM', 'active' => true],
                ['text' => 'Atención preferencial por LYRIUM', 'active' => true],
            ],
            'detailed_benefits' => [
                ['emoji' => '🏆', 'title' => 'Logo en banners principales', 'description' => 'Tu logotipo se exhibirá en los banners principales de LYRIUM', 'color' => '#3b82f6'],
                ['emoji' => '🎓', 'title' => 'Capacitaciones online', 'description' => 'Accede a sesiones de capacitación online en postventa', 'color' => '#3b82f6'],
                ['emoji' => '⭐', 'title' => 'Producto recomendado', 'description' => 'Recibe una insignia de producto recomendado por LYRIUM', 'color' => '#3b82f6'],
                ['emoji' => '💬', 'title' => 'Atención preferencial', 'description' => 'Soporte prioritario para resolver tus consultas más rápido', 'color' => '#3b82f6'],
            ],
        ]);

        Plan::updateOrCreate(['slug' => 'especial'], [
            'name' => 'ESPECIAL',
            'monthly_fee' => 89.90,
            'commission_rate' => 0.1500,
            'has_membership_fee' => true,
            'is_active' => true,
            'timeline_icon' => 'crown',
            'badge' => 'Premium',
            'description' => 'Máxima visibilidad y herramientas exclusivas para los mejores vendedores.',
            'css_color' => '#8b5cf6',
            'accent_color' => '#7c3aed',
            'requires_payment' => true,
            'enable_claim_lock' => true,
            'claim_months' => 6,
            'subscribe_button_text' => 'Suscribirse',
            'currency' => 'S/',
            'period' => '/mes',
            'price_annual' => 899.00,
            'price_text' => null,
            'price_subtext' => '/mes',
            'use_price_mode' => true,
            'compact_visible_count' => 5,
            'features' => [
                ['text' => 'Todo lo del plan CRECE', 'active' => true],
                ['text' => 'Banner personalizado en homepage de LYRIUM', 'active' => true],
                ['text' => 'Servicio de atención al cliente personalizado', 'active' => true],
                ['text' => 'Análisis de mercado y competidores', 'active' => true],
                ['text' => 'Prioridad en búsquedas y filtros', 'active' => true],
            ],
            'detailed_benefits' => [
                ['emoji' => '🖼️', 'title' => 'Banner personalizado', 'description' => 'Tu banner personalizado aparecerá en el homepage de LYRIUM', 'color' => '#8b5cf6'],
                ['emoji' => '👤', 'title' => 'Atención al cliente personalizada', 'description' => 'Servicio dedicado de atención al cliente para ti', 'color' => '#8b5cf6'],
                ['emoji' => '📊', 'title' => 'Análisis de mercado', 'description' => 'Reports y análisis de mercado y competidores', 'color' => '#8b5cf6'],
                ['emoji' => '🔝', 'title' => 'Prioridad en búsquedas', 'description' => 'Tus productos aparecerán primero en búsquedas y filtros', 'color' => '#8b5cf6'],
            ],
        ]);
    }
}
