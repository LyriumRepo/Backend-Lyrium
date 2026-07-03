<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Catalogs\SystemEvents;
use App\Models\AuditLogArchived;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class AuditPurgeCommand extends Command
{
    protected $signature = 'audit:purge
        {--months=36 : Meses de antigüedad para purgar}
        {--chunk=500 : Registros por lote}
        {--dry-run : Solo mostrar cuántos se purgarían}';

    protected $description = 'Respalda y elimina registros de auditoría > 36 meses';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subMonths($months);

        $total = AuditLogArchived::where('created_at', '<', $cutoff)->count();

        if ($total === 0) {
            $this->info('No hay registros para purgar.');

            return self::SUCCESS;
        }

        $this->info("Registros a purgar: {$total}");

        if ($dryRun) {
            $this->warn('Modo dry-run: no se realizaron cambios.');

            return self::SUCCESS;
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = "audit/archive/{$timestamp}";
        $jsonPath = "{$backupDir}/audit-purge-{$timestamp}.json";
        $csvPath = "{$backupDir}/audit-purge-{$timestamp}.csv";

        Storage::makeDirectory($backupDir);

        $purged = 0;

        AuditLogArchived::where('created_at', '<', $cutoff)
            ->chunkById($chunkSize, function ($logs) use ($jsonPath, $csvPath, &$purged) {
                $jsonHeader = count($purged) === 0;
                $csvHeader = count($purged) === 0;

                $handle = Storage::path($csvPath);
                $csvExists = file_exists($handle);

                $csv = fopen($handle, $csvExists ? 'a' : 'w');

                if (! $csvExists || $csvHeader) {
                    fputcsv($csv, [
                        'id', 'event', 'module', 'severity', 'description',
                        'user_id', 'user_email', 'ip_address', 'source',
                        'correlation_id', 'created_at',
                    ]);
                }

                $jsonRows = [];

                foreach ($logs as $log) {
                    $jsonRows[] = $log->toArray();

                    fputcsv($csv, [
                        $log->id,
                        $log->event,
                        $log->module,
                        $log->severity,
                        $log->description,
                        $log->user_id,
                        $log->user_email,
                        $log->ip_address,
                        $log->source,
                        $log->correlation_id,
                        $log->created_at?->toISOString(),
                    ]);
                }

                fclose($csv);

                $existing = [];
                if (Storage::exists($jsonPath)) {
                    $existing = json_decode(Storage::get($jsonPath), true) ?? [];
                }

                Storage::put($jsonPath, json_encode(
                    array_merge($existing, $jsonRows),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                ));

                $ids = $logs->pluck('id')->toArray();
                AuditLogArchived::whereIn('id', $ids)->delete();

                $purged += count($logs);
                $this->info("Purged {$purged} registros...");
            });

        app(AuditService::class)->record(
            event: SystemEvents::SCHEDULER_EXECUTED,
            module: 'system',
            description: "Purgados {$purged} registros de auditoría (> {$months} meses)",
            severity: 'info',
            source: AuditService::SOURCE_SCHEDULER,
            metadata: [
                'command' => 'audit:purge',
                'purged_count' => $purged,
                'backup_path' => $backupDir,
                'cutoff_months' => $months,
            ],
        );

        $this->info("Purga completada: {$purged} registros respaldados y eliminados.");
        $this->info("Backup en: storage/app/{$backupDir}");

        return self::SUCCESS;
    }
}
