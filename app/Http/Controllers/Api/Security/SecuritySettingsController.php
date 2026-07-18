<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SecuritySettingsController extends Controller
{
    private const SECURITY_CATEGORY = 'security';

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(): JsonResponse
    {
        $configs = SystemConfig::getByCategory(self::SECURITY_CATEGORY)->get();

        if ($configs->isEmpty()) {
            $this->seedDefaults();
            $configs = SystemConfig::getByCategory(self::SECURITY_CATEGORY)->get();
        }

        $settings = $configs->mapWithKeys(fn ($config) => [
            $config->key => match ($config->type) {
                'boolean' => $config->value === 'true' || $config->value === '1',
                'integer' => (int) $config->value,
                'json' => json_decode($config->value, true),
                default => $config->value,
            },
        ]);

        return $this->success($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'autoblock_enabled' => ['sometimes', 'boolean'],
            'autoblock_threshold' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'autoblock_window_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'autoblock_duration_minutes' => ['sometimes', 'integer', 'min:0', 'max:43200'],
            'whitelist_enabled' => ['sometimes', 'boolean'],
            'max_login_attempts' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        foreach ($validated as $key => $value) {
            SystemConfig::setByKey($key, $value);
        }

        $this->auditService->record(
            event: 'config.security.updated',
            module: 'security',
            description: 'Configuración de seguridad actualizada',
            severity: 'critical',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'updated_keys' => array_keys($validated),
            ],
        );

        $request->attributes->set('_audit_recorded', true);

        return $this->success($this->getSettingsArray(), 'Configuración de seguridad actualizada.');
    }

    public function reset(Request $request): JsonResponse
    {
        SystemConfig::getByCategory(self::SECURITY_CATEGORY)->update(['value' => null]);

        $this->seedDefaults();

        $this->auditService->record(
            event: 'config.security.updated',
            module: 'security',
            description: 'Configuración de seguridad restablecida a valores por defecto',
            severity: 'warning',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: ['action' => 'reset'],
        );

        $request->attributes->set('_audit_recorded', true);

        return $this->success($this->getSettingsArray(), 'Configuración restablecida a valores por defecto.');
    }

    private function getSettingsArray(): array
    {
        $configs = SystemConfig::getByCategory(self::SECURITY_CATEGORY)->get();

        return $configs->mapWithKeys(fn ($config) => [
            $config->key => match ($config->type) {
                'boolean' => $config->value === 'true' || $config->value === '1',
                'integer' => (int) $config->value,
                'json' => json_decode($config->value, true),
                default => $config->value,
            },
        ])->toArray();
    }

    private function seedDefaults(): void
    {
        $defaults = [
            ['key' => 'autoblock_enabled', 'value' => 'true', 'type' => 'boolean', 'name' => 'Auto-bloqueo habilitado', 'description' => 'Activar bloqueo automático por intentos fallidos'],
            ['key' => 'autoblock_threshold', 'value' => '10', 'type' => 'integer', 'name' => 'Umbral de auto-bloqueo', 'description' => 'Intentos fallidos antes de bloquear IP'],
            ['key' => 'autoblock_window_minutes', 'value' => '10', 'type' => 'integer', 'name' => 'Ventana de tiempo', 'description' => 'Minutos en los que se evalúan los intentos fallidos'],
            ['key' => 'autoblock_duration_minutes', 'value' => '20', 'type' => 'integer', 'name' => 'Duración del bloqueo', 'description' => 'Minutos que dura el bloqueo automático (0 = indefinido)'],
            ['key' => 'whitelist_enabled', 'value' => 'true', 'type' => 'boolean', 'name' => 'Whitelist habilitada', 'description' => 'Permitir bypass de rate limiting para IPs en whitelist'],
            ['key' => 'max_login_attempts', 'value' => '10', 'type' => 'integer', 'name' => 'Intentos máximos de login', 'description' => 'Intentos máximos por minuto antes de rate limiting'],
        ];

        foreach ($defaults as $cfg) {
            SystemConfig::firstOrCreate(
                ['key' => $cfg['key'], 'category' => self::SECURITY_CATEGORY],
                $cfg + ['category' => self::SECURITY_CATEGORY, 'is_public' => false],
            );
        }
    }
}
