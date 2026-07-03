<?php

declare(strict_types=1);

namespace App\Catalogs;

final class SystemEvents
{
    const EXCEPTION = 'system.exception';
    const ERROR = 'system.error';
    const CACHE_CLEARED = 'system.cache.cleared';
    const MAINTENANCE_ENABLED = 'system.maintenance.enabled';
    const MAINTENANCE_DISABLED = 'system.maintenance.disabled';
    const QUEUE_FAILED = 'system.queue.failed';
    const SCHEDULER_EXECUTED = 'system.scheduler.executed';
    const DATABASE_BACKUP = 'system.database.backup';
    const HEALTH_CHECK_FAILED = 'system.health.check.failed';
}
