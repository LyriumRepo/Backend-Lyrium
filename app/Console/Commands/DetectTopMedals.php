<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditService;
use App\Services\TopMedalService;
use Illuminate\Console\Command;

final class DetectTopMedals extends Command
{
    protected $signature = 'top-medals:detect';

    protected $description = 'Detect entries and exits from Top 100 rankings and update medal states';

    public function handle(TopMedalService $topMedalService): int
    {
        $this->info('Detecting Top 100 medal changes...');

        $changes = $topMedalService->detect();

        $this->line(sprintf('  Entries detected:     %d', count($changes['entered'])));
        $this->line(sprintf('  Re-entries detected:  %d', count($changes['reentered'])));
        $this->line(sprintf('  Grace periods started: %d', count($changes['grace_started'])));
        $this->line(sprintf('  Suspensions executed:  %d', count($changes['suspended'])));

        foreach ($changes['entered'] as $entry) {
            $this->line("  New: {$entry['type']} #{$entry['id']} at position {$entry['position']}");
        }

        foreach ($changes['reentered'] as $entry) {
            $this->line("  Reactivated: {$entry['type']} #{$entry['id']} at position {$entry['position']}");
        }

        foreach ($changes['grace_started'] as $exit) {
            $this->line("  Grace period: {$exit['type']} #{$exit['id']} (30 days)");
        }

        foreach ($changes['suspended'] as $susp) {
            $this->line("  Suspended: {$susp['type']} #{$susp['id']}");
        }

        $total = array_sum(array_map('count', $changes));
        $this->info("Done. {$total} change(s) processed.");

        app(AuditService::class)->record(
            source: AuditService::SOURCE_SCHEDULER,
            event: 'system.scheduler.executed',
            module: 'system',
            description: 'Tarea programada ejecutada: top-medals:detect',
            severity: 'info',
            metadata: ['command' => 'top-medals:detect'],
        );

        return self::SUCCESS;
    }
}
