<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['key' => 'primary_color', 'name' => 'Color Primario', 'value' => '#22c55e', 'type' => 'color', 'category' => 'colors', 'description' => 'Color principal de la marca', 'is_public' => true],
            ['key' => 'secondary_color', 'name' => 'Color Secundario', 'value' => '#16a34a', 'type' => 'color', 'category' => 'colors', 'description' => 'Color secundario de la marca', 'is_public' => true],
            ['key' => 'accent_color', 'name' => 'Color de Acento', 'value' => '#f59e0b', 'type' => 'color', 'category' => 'colors', 'description' => 'Color de acento para botones y highlights', 'is_public' => true],
            ['key' => 'background_color', 'name' => 'Color de Fondo', 'value' => '#ffffff', 'type' => 'color', 'category' => 'colors', 'description' => 'Color de fondo principal', 'is_public' => true],
            ['key' => 'text_primary_color', 'name' => 'Color de Texto Principal', 'value' => '#1f2937', 'type' => 'color', 'category' => 'colors', 'description' => 'Color del texto principal', 'is_public' => true],
            ['key' => 'text_secondary_color', 'name' => 'Color de Texto Secundario', 'value' => '#6b7280', 'type' => 'color', 'category' => 'colors', 'description' => 'Color del texto secundario', 'is_public' => true],
            ['key' => 'error_color', 'name' => 'Color de Error', 'value' => '#ef4444', 'type' => 'color', 'category' => 'colors', 'description' => 'Color para mensajes de error', 'is_public' => true],
            ['key' => 'success_color', 'name' => 'Color de Éxito', 'value' => '#22c55e', 'type' => 'color', 'category' => 'colors', 'description' => 'Color para mensajes de éxito', 'is_public' => true],
            ['key' => 'site_name', 'name' => 'Nombre del Sitio', 'value' => 'LYRIUM', 'type' => 'string', 'category' => 'general', 'description' => 'Nombre de la plataforma', 'is_public' => true],
            ['key' => 'site_description', 'name' => 'Descripción del Sitio', 'value' => 'Biomarketplace para productos orgánicos', 'type' => 'string', 'category' => 'general', 'description' => 'Descripción breve de la plataforma', 'is_public' => true],
            ['key' => 'contact_email', 'name' => 'Email de Contacto', 'value' => 'contacto@lyrium.com', 'type' => 'string', 'category' => 'general', 'description' => 'Email de contacto principal', 'is_public' => true],
            ['key' => 'commission_tiers', 'name' => 'Tasas de Comisión', 'value' => '[{"min":0,"max":400,"rate":0.15},{"min":401,"max":800,"rate":0.14},{"min":801,"max":1200,"rate":0.13},{"min":1201,"max":null,"rate":0.12}]', 'type' => 'json', 'category' => 'commission', 'description' => 'Tasas de comisión por categoría de venta (min, max, rate)', 'is_public' => false],

            // Security
            ['key' => 'autoblock_enabled', 'name' => 'Auto-bloqueo habilitado', 'value' => 'true', 'type' => 'boolean', 'category' => 'security', 'description' => 'Activar bloqueo automático por intentos fallidos', 'is_public' => false],
            ['key' => 'autoblock_threshold', 'name' => 'Umbral de auto-bloqueo', 'value' => '10', 'type' => 'integer', 'category' => 'security', 'description' => 'Intentos fallidos antes de bloquear IP', 'is_public' => false],
            ['key' => 'autoblock_window_minutes', 'name' => 'Ventana de tiempo', 'value' => '10', 'type' => 'integer', 'category' => 'security', 'description' => 'Minutos en los que se evalúan los intentos fallidos', 'is_public' => false],
            ['key' => 'autoblock_duration_minutes', 'name' => 'Duración del bloqueo', 'value' => '20', 'type' => 'integer', 'category' => 'security', 'description' => 'Minutos que dura el bloqueo automático (0 = indefinido)', 'is_public' => false],
            ['key' => 'whitelist_enabled', 'name' => 'Whitelist habilitada', 'value' => 'true', 'type' => 'boolean', 'category' => 'security', 'description' => 'Permitir bypass de rate limiting para IPs en whitelist', 'is_public' => false],
            ['key' => 'max_login_attempts', 'name' => 'Intentos máximos de login', 'value' => '10', 'type' => 'integer', 'category' => 'security', 'description' => 'Intentos máximos por minuto antes de rate limiting', 'is_public' => false],
        ];

        foreach ($configs as $config) {
            SystemConfig::firstOrCreate(['key' => $config['key']], $config);
        }
    }
}
