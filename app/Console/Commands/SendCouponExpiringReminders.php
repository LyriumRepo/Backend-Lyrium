<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Notifications\CouponExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendCouponExpiringReminders extends Command
{
    protected $signature = 'app:send-coupon-expiring-reminders';

    protected $description = 'Notifica al vendedor cuando un cupón activo vence en 3 o 1 día(s)';

    public function handle(): int
    {
        $daysToCheck = [3, 1];
        $sent = 0;

        foreach ($daysToCheck as $days) {
            $targetDate = Carbon::today()->addDays($days)->toDateString();

            Coupon::with(['store.owner'])
                ->where('is_active', true)
                ->whereDate('expires_at', $targetDate)
                ->get()
                ->each(function (Coupon $coupon) use ($days, &$sent) {
                    $owner = $coupon->store?->owner;
                    if (! $owner) {
                        return;
                    }

                    try {
                        $owner->notify(new CouponExpiringNotification($coupon, $days));
                        $sent++;
                        $this->line("  ✓ {$owner->email} — cupón \"{$coupon->code}\" vence en {$days} día(s)");
                    } catch (\Throwable $e) {
                        Log::error('[CouponExpiring] Error notificando', [
                            'coupon_id' => $coupon->id, 'owner_id' => $owner->id, 'error' => $e->getMessage(),
                        ]);
                        $this->error("  ✗ {$owner->email}: {$e->getMessage()}");
                    }
                });
        }

        $this->info("Recordatorios de cupones enviados: {$sent}");
        return self::SUCCESS;
    }
}
