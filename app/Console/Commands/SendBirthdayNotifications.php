<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BirthdayNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class SendBirthdayNotifications extends Command
{
    protected $signature = 'birthday:send';

    protected $description = 'Envía notificaciones de cumpleaños (email + push) a los usuarios que cumplen años hoy';

    public function handle(): int
    {
        $today = today()->format('m-d');

        if (Cache::get("birthday_sent_{$today}")) {
            $this->info('Las notificaciones de cumpleaños ya fueron enviadas hoy.');

            return self::SUCCESS;
        }

        $users = User::whereNotNull('birthday')
            ->whereMonth('birthday', '=', now()->month)
            ->whereDay('birthday', '=', now()->day)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No hay usuarios que cumplan años hoy.');

            return self::SUCCESS;
        }

        $this->info("Enviando notificaciones de cumpleaños a {$users->count()} usuario(s)...");

        $sent = 0;

        foreach ($users as $user) {
            try {
                $user->notify(new BirthdayNotification);
                $sent++;
                $this->line("  ✓ {$user->name} ({$user->email})");
            } catch (\Throwable $e) {
                Log::error('[Birthday] Error al notificar a usuario', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ {$user->name} ({$user->email}): {$e->getMessage()}");
            }
        }

        Cache::put("birthday_sent_{$today}", true, now()->endOfDay());

        $this->info("Notificaciones enviadas: {$sent} de {$users->count()}");

        return self::SUCCESS;
    }
}
