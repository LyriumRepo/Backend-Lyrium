<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Store;
use App\Models\TopMedal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TopMedalService
{
    private const GRACE_DAYS = 30;

    public function __construct(
        private readonly RankingService $rankingService,
    ) {}

    /**
     * Detecta entradas, salidas y vencimientos de grace period.
     * Devuelve un resumen de cambios realizados.
     */
    public function detect(): array
    {
        $changes = [
            'entered' => [],
            'exited' => [],
            'reentered' => [],
            'suspended' => [],
            'grace_started' => [],
        ];

        DB::transaction(function () use (&$changes) {
            $this->detectEntityType('store', $this->rankingService->getTopStores(50), $changes);
            $this->detectEntityType('product', $this->rankingService->getTopProducts(100), $changes);
            $this->detectEntityType('service', $this->rankingService->getTopServices(100), $changes);

            $this->processGraceExpirations($changes);
        });

        return $changes;
    }

    private function detectEntityType(string $entityType, Collection $currentRanking, array &$changes): void
    {
        $currentIds = $currentRanking->pluck('id')->map(fn ($id) => (string) $id);

        $existingMedals = TopMedal::where('entity_type', $entityType)
            ->whereIn('status', ['pending', 'approved', 'suspended'])
            ->get()
            ->keyBy(fn ($m) => (string) $m->medalable_id);

        $existingIds = $existingMedals->keys();

        // Entradas: IDs en ranking actual pero no en medals
        $newIds = $currentIds->diff($existingIds);
        foreach ($newIds as $index => $id) {
            $position = $currentRanking->search(fn ($item) => (string) $item->id === $id) + 1;
            $entity = $currentRanking->firstWhere('id', (int) $id);

            TopMedal::create([
                'medalable_type' => $this->getMorphClass($entityType),
                'medalable_id' => (int) $id,
                'entity_type' => $entityType,
                'rank_position' => $position,
                'status' => 'pending',
                'visible' => false,
                'times_entered' => 1,
                'times_exited' => 0,
                'detected_at' => now(),
            ]);

            $changes['entered'][] = ['type' => $entityType, 'id' => (int) $id, 'position' => $position];
        }

        // Re-ingresos: IDs suspendidas que volvieron al ranking
        $suspendedMedals = $existingMedals->filter(fn ($m) => $m->status === 'suspended');
        $reenteredIds = $currentIds->intersect($suspendedMedals->keys());

        foreach ($reenteredIds as $id) {
            $medal = $suspendedMedals->get($id);
            $position = $currentRanking->search(fn ($item) => (string) $item->id === $id) + 1;

            $medal->update([
                'status' => 'approved',
                'rank_position' => $position,
                'visible' => true,
                'times_entered' => $medal->times_entered + 1,
                'suspended_at' => null,
                'grace_ends_at' => null,
            ]);

            $changes['reentered'][] = ['type' => $entityType, 'id' => (int) $id, 'position' => $position];
        }

        // Salidas: IDs en medals (approved) que ya no están en ranking
        $approvedMedals = $existingMedals->filter(fn ($m) => $m->status === 'approved');
        $exitedIds = $approvedMedals->keys()->diff($currentIds);

        foreach ($exitedIds as $id) {
            $medal = $approvedMedals->get($id);

            if ($medal->grace_ends_at === null) {
                $medal->update([
                    'grace_ends_at' => now()->addDays(self::GRACE_DAYS),
                    'rank_position' => null,
                ]);

                $changes['grace_started'][] = ['type' => $entityType, 'id' => (int) $id];
            }
        }
    }

    private function processGraceExpirations(array &$changes): void
    {
        $expired = TopMedal::where('status', 'approved')
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<=', now())
            ->get();

        foreach ($expired as $medal) {
            $medal->update([
                'status' => 'suspended',
                'visible' => false,
                'suspended_at' => now(),
                'times_exited' => $medal->times_exited + 1,
                'grace_ends_at' => null,
            ]);

            $changes['suspended'][] = [
                'type' => $medal->entity_type,
                'id' => (int) $medal->medalable_id,
            ];
        }
    }

    private function getMorphClass(string $entityType): string
    {
        return match ($entityType) {
            'store' => Store::class,
            'product' => 'App\Models\Product',
            'service' => 'App\Models\Service',
            default => throw new \InvalidArgumentException("Unknown entity type: {$entityType}"),
        };
    }
}
