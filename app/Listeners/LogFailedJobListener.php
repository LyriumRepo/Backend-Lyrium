<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Catalogs\SystemEvents;
use App\Services\AuditService;
use Illuminate\Queue\Events\JobFailed;

final class LogFailedJobListener
{
    public function __construct(private readonly AuditService $auditService) {}

    public function handle(JobFailed $event): void
    {
        $this->auditService->record(
            event: SystemEvents::QUEUE_FAILED,
            module: 'system',
            description: 'Job encolado fallido: ' . $event->job->resolveName(),
            severity: 'warning',
            source: AuditService::SOURCE_QUEUE,
            metadata: [
                'job' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'exception' => $event->exception?->getMessage(),
                'queue' => $event->job->getQueue(),
            ],
        );
    }
}
