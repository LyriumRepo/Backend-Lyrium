<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class AuditCoverageTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->auditService = app(AuditService::class);
    }

    public function test_all_catalog_events_have_severity_defined(): void
    {
        $events = $this->collectAllCatalogEvents();
        $severityConfig = config('audit.severity', []);

        $missing = [];

        foreach ($events as $event) {
            if (! array_key_exists($event, $severityConfig)) {
                $missing[] = $event;
            }
        }

        $this->assertEmpty(
            $missing,
            "Los siguientes eventos del catálogo no tienen severidad definida en config/audit.php:\n" .
            implode("\n", $missing),
        );
    }

    public function test_all_catalog_events_can_be_recorded(): void
    {
        $events = $this->collectAllCatalogEvents();

        foreach ($events as $event) {
            $module = explode('.', $event)[0];

            $log = $this->auditService->record(
                event: $event,
                module: $module,
                description: "Test coverage: {$event}",
                severity: 'info',
                success: true,
                source: AuditService::SOURCE_SYSTEM,
                metadata: ['test' => true],
            );

            $this->assertNotNull($log);
            $this->assertSame($event, $log->event);
            $this->assertSame($module, $log->module);
            $this->assertSame('info', $log->severity);
        }
    }

    public function test_security_events_can_be_recorded(): void
    {
        $event = 'security.audit.settings.changed';
        $log = $this->auditService->record(
            event: $event,
            module: 'security',
            description: 'Test: configuración de auditoría actualizada',
            severity: 'warning',
            success: true,
            source: AuditService::SOURCE_SYSTEM,
        );

        $this->assertNotNull($log);
        $this->assertSame($event, $log->event);
        $this->assertSame('warning', $log->severity);
    }

    public function test_system_events_can_be_recorded(): void
    {
        $event = 'system.cache.cleared';
        $log = $this->auditService->record(
            event: $event,
            module: 'system',
            description: 'Test: caché limpiada',
            severity: 'info',
            success: true,
            source: AuditService::SOURCE_SYSTEM,
        );

        $this->assertNotNull($log);
        $this->assertSame($event, $log->event);
        $this->assertSame('info', $log->severity);
    }

    public function test_critical_severity_events_are_recorded(): void
    {
        $event = 'system.exception';
        $log = $this->auditService->record(
            event: $event,
            module: 'system',
            description: 'Test: excepción crítica',
            severity: 'critical',
            source: AuditService::SOURCE_SYSTEM,
        );

        $this->assertNotNull($log);
        $this->assertSame('critical', $log->severity);
    }

    public function test_audit_service_persists_and_returns_log(): void
    {
        $log = $this->auditService->record(
            event: 'system.health.check.failed',
            module: 'system',
            description: 'Test coverage: health check failed',
            severity: 'critical',
            source: AuditService::SOURCE_SYSTEM,
            metadata: ['test' => true, 'component' => 'database'],
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event' => 'system.health.check.failed',
            'severity' => 'critical',
            'source' => 'system',
        ]);

        $this->assertNotNull($log->correlation_id);
        $this->assertIsString($log->correlation_id);
    }

    public function test_config_audit_has_all_required_sections(): void
    {
        $config = config('audit');

        $this->assertArrayHasKey('severity', $config);
        $this->assertArrayHasKey('async_events', $config);
        $this->assertArrayHasKey('patterns', $config);
        $this->assertArrayHasKey('retention', $config);
        $this->assertArrayHasKey('live', $config['retention']);
        $this->assertArrayHasKey('archive', $config['retention']);

        $this->assertSame(12, $config['retention']['live']);
        $this->assertSame(36, $config['retention']['archive']);
    }

    public function test_severity_config_contains_no_null_values(): void
    {
        $severityConfig = config('audit.severity', []);

        $nullValues = [];

        foreach ($severityConfig as $event => $severity) {
            if ($severity === null) {
                $nullValues[] = $event;
            }
        }

        $this->assertEmpty(
            $nullValues,
            "Los siguientes eventos tienen severidad null en config/audit.php:\n" .
            implode("\n", $nullValues),
        );
    }

    private function collectAllCatalogEvents(): array
    {
        $events = [];
        $files = glob(app_path('Catalogs/*.php'));

        foreach ($files as $file) {
            $className = 'App\\Catalogs\\' . basename($file, '.php');

            if (! class_exists($className)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($className);
                $constants = $reflection->getConstants();

                foreach ($constants as $name => $value) {
                    if (is_string($value)) {
                        $events[] = $value;
                    }
                }
            } catch (\ReflectionException) {
                continue;
            }
        }

        sort($events);

        return $events;
    }
}
