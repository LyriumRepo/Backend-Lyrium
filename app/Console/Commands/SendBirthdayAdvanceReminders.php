<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BirthdayAdvanceNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class SendBirthdayAdvanceReminders extends Command
{
    protected $signature = 'birthday:advance';

    protected $description = 'Envía correos de anticipación de cumpleaños 14 días antes';

    private const DAYS_AHEAD = 14;

    public function handle(): int
    {
        $targetDate = now()->addDays(self::DAYS_AHEAD);
        $cacheKey = 'birthday_advance_sent_' . $targetDate->format('m-d');

        if (Cache::get($cacheKey)) {
            $this->info('Los recordatorios anticipados ya fueron enviados hoy.');
            return self::SUCCESS;
        }

        $users = User::whereNotNull('birthday')
            ->whereMonth('birthday', '=', $targetDate->month)
            ->whereDay('birthday', '=', $targetDate->day)
            ->whereNotNull('email_verified_at')
            ->where('is_banned', false)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No hay usuarios con cumpleaños en ' . self::DAYS_AHEAD . ' días.');
            Cache::put($cacheKey, true, now()->endOfDay());
            return self::SUCCESS;
        }

        $this->info("Enviando recordatorios anticipados a {$users->count()} usuario(s)...");

        $sent = 0;

        foreach ($users as $user) {
            try {
                $user->notify(new BirthdayAdvanceNotification());
                $sent++;
                $this->line("  ✓ {$user->name} ({$user->email})");
            } catch (\Throwable $e) {
                Log::error('[Birthday Advance] Error al notificar a usuario', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'error'   => $e->getMessage(),
                ]);
                $this->error("  ✗ {$user->name}: {$e->getMessage()}");
            }
        }

        Cache::put($cacheKey, true, now()->endOfDay());
        $this->info("Recordatorios enviados: {$sent} de {$users->count()}");

        return self::SUCCESS;
    }
}
