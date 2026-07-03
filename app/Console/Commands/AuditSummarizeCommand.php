<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Catalogs\SystemEvents;
use App\Models\AuditLog;
use App\Models\AuditLogSummary;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AuditSummarizeCommand extends Command
{
    protected $signature = 'audit:summarize
        {--date= : Fecha a procesar (Y-m-d, por defecto ayer)}
        {--all : Procesar todas las fechas sin resumen}';

    protected $description = 'Construye resúmenes diarios de auditoría en audit_log_summaries';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->processAll();
        }

        $dateStr = $this->option('date') ?? now()->subDay()->format('Y-m-d');
        $date = Carbon::parse($dateStr);

        $this->info("Generando resumen para: {$date->format('Y-m-d')}");

        $this->generateSummaryForDate($date);

        $this->info('Resumen generado correctamente.');

        return self::SUCCESS;
    }

    private function generateSummaryForDate(Carbon $date): int
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $summaries = AuditLog::whereBetween('created_at', [$dayStart, $dayEnd])
            ->select('module', 'severity', DB::raw('COUNT(*) as total'))
            ->groupBy('module', 'severity')
            ->get();

        $inserted = 0;

        foreach ($summaries as $row) {
            AuditLogSummary::updateOrCreate(
                [
                    'date' => $date->format('Y-m-d'),
                    'module' => $row->module,
                    'severity' => $row->severity,
                ],
                ['total' => $row->total],
            );

            $inserted++;
        }

        app(AuditService::class)->record(
            event: SystemEvents::SCHEDULER_EXECUTED,
            module: 'system',
            description: "Resumen diario generado para {$date->format('Y-m-d')}: {$inserted} módulos",
            severity: 'info',
            source: AuditService::SOURCE_SCHEDULER,
            metadata: [
                'command' => 'audit:summarize',
                'date' => $date->format('Y-m-d'),
                'modules_summarized' => $inserted,
            ],
        );

        return $inserted;
    }

    private function processAll(): int
    {
        $dates = AuditLog::query()
            ->select(DB::raw('DISTINCT DATE(created_at) as date'))
            ->whereRaw('DATE(created_at) NOT IN (SELECT DISTINCT date FROM audit_log_summaries)')
            ->orderBy('date')
            ->pluck('date');

        if ($dates->isEmpty()) {
            $this->info('Todas las fechas ya tienen resumen.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($dates as $dateStr) {
            $date = Carbon::parse($dateStr);
            $count = $this->generateSummaryForDate($date);
            $this->info("  {$dateStr}: {$count} módulos");
            $total += $count;
        }

        $this->info("Resumen completo: {$total} módulos en {$dates->count()} fechas.");

        return self::SUCCESS;
    }
}
