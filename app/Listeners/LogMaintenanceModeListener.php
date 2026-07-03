<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Catalogs\SystemEvents;
use App\Services\AuditService;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;

final class LogMaintenanceModeListener
{
    public function __construct(private readonly AuditService $auditService) {}

    public function handleEnabled(MaintenanceModeEnabled $event): void
    {
        $this->auditService->record(
            event: SystemEvents::MAINTENANCE_ENABLED,
            module: 'system',
            description: 'Modo mantenimiento activado',
            severity: 'critical',
            source: AuditService::SOURCE_SYSTEM,
        );
    }

    public function handleDisabled(MaintenanceModeDisabled $event): void
    {
        $this->auditService->record(
            event: SystemEvents::MAINTENANCE_DISABLED,
            module: 'system',
            description: 'Modo mantenimiento desactivado',
            severity: 'info',
            source: AuditService::SOURCE_SYSTEM,
        );
    }
}
