<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\PlanExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendPlanExpiringReminders extends Command
{
    protected $signature = 'app:send-plan-expiring-reminders';

    protected $description = 'Envía correo al vendedor cuando su plan vence en 7, 3 o 1 día(s)';

    public function handle(): int
    {
        $daysToCheck = [7, 3, 1];
        $sent = 0;

        foreach ($daysToCheck as $days) {
            $targetDate = Carbon::today()->addDays($days)->toDateString();

            Subscription::with(['store.owner', 'plan'])
                ->where('status', 'active')
                ->whereDate('ends_at', $targetDate)
                ->get()
                ->each(function (Subscription $subscription) use ($days, &$sent) {
                    $owner = $subscription->store?->owner;
                    if (! $owner) {
                        return;
                    }

                    try {
                        $owner->notify(new PlanExpiringNotification($subscription, $days));
                        $sent++;
                        $this->line("  ✓ {$owner->email} — {$subscription->plan?->name} vence en {$days} día(s)");
                    } catch (\Throwable $e) {
                        Log::error('[PlanExpiring] Error notificando', [
                            'subscription_id' => $subscription->id, 'owner_id' => $owner->id, 'error' => $e->getMessage(),
                        ]);
                        $this->error("  ✗ {$owner->email}: {$e->getMessage()}");
                    }
                });
        }

        $this->info("Recordatorios enviados: {$sent}");
        return self::SUCCESS;
    }
}
