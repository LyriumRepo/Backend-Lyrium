<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Catalogs\SystemEvents;
use App\Models\AuditLog;
use App\Models\AuditLogArchived;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AuditArchiveCommand extends Command
{
    protected $signature = 'audit:archive
        {--months=12 : Meses de antigüedad para archivar}
        {--chunk=500 : Registros por lote}
        {--dry-run : Solo mostrar cuántos se archivarían}';

    protected $description = 'Archiva registros de auditoría > 12 meses a audit_logs_archived';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subMonths($months);

        $total = AuditLog::where('created_at', '<', $cutoff)->count();

        if ($total === 0) {
            $this->info('No hay registros para archivar.');

            return self::SUCCESS;
        }

        $this->info("Registros a archivar: {$total}");

        if ($dryRun) {
            $this->warn('Modo dry-run: no se realizaron cambios.');

            return self::SUCCESS;
        }

        $archived = 0;

        AuditLog::where('created_at', '<', $cutoff)
            ->chunkById($chunkSize, function ($logs) use (&$archived) {
                $rows = [];

                foreach ($logs as $log) {
                    $rows[] = [
                        'user_id' => $log->user_id,
                        'user_email' => $log->user_email,
                        'user_role' => $log->user_role,
                        'session_id' => $log->session_id,
                        'correlation_id' => $log->correlation_id,
                        'event' => $log->event,
                        'module' => $log->module,
                        'severity' => $log->severity,
                        'description' => $log->description,
                        'success' => $log->success,
                        'source' => $log->source,
                        'auditable_type' => $log->auditable_type,
                        'auditable_id' => $log->auditable_id,
                        'old_values' => $log->old_values !== null ? json_encode($log->old_values) : null,
                        'new_values' => $log->new_values !== null ? json_encode($log->new_values) : null,
                        'metadata' => $log->metadata !== null ? json_encode($log->metadata) : null,
                        'ip_address' => $log->ip_address,
                        'user_agent' => $log->user_agent,
                        'request_method' => $log->request_method,
                        'request_url' => $log->request_url,
                        'response_code' => $log->response_code,
                        'created_at' => $log->created_at,
                    ];
                }

                AuditLogArchived::insert($rows);

                $ids = $logs->pluck('id')->toArray();
                AuditLog::whereIn('id', $ids)->delete();

                $archived += count($logs);
                $this->info("Archivados {$archived} registros...");
            });

        app(AuditService::class)->record(
            event: SystemEvents::SCHEDULER_EXECUTED,
            module: 'system',
            description: "Archivados {$archived} registros de auditoría (> {$months} meses)",
            severity: 'info',
            source: AuditService::SOURCE_SCHEDULER,
            metadata: [
                'command' => 'audit:archive',
                'archived_count' => $archived,
                'cutoff_months' => $months,
            ],
        );

        $this->info("Archivado completado: {$archived} registros movidos a audit_logs_archived.");

        return self::SUCCESS;
    }
}
