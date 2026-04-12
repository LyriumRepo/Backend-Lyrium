<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\User;
use App\Notifications\PendingStoreOverdueNotification;
use Illuminate\Console\Command;

final class CheckPendingStoresSLA extends Command
{
    protected $signature   = 'stores:check-sla';
    protected $description = 'Notifica a los administradores sobre tiendas pendientes de aprobación que superaron el SLA de 72 horas';

    public function handle(): int
    {
        $overdueStores = Store::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(72))
            ->whereNull('sla_notified_at')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($overdueStores->isEmpty()) {
            $this->info('No hay tiendas pendientes con SLA vencido.');

            return self::SUCCESS;
        }

        $admins = User::role('administrator')->get();

        if ($admins->isEmpty()) {
            $this->warn('No se encontraron administradores para notificar.');

            return self::SUCCESS;
        }

        $oldest = $overdueStores->first();
        $count  = $overdueStores->count();

        foreach ($admins as $admin) {
            $admin->notify(new PendingStoreOverdueNotification($count, $oldest));
        }

        // Marcar las tiendas como notificadas para no repetir la alerta
        Store::whereIn('id', $overdueStores->pluck('id'))->update([
            'sla_notified_at' => now(),
        ]);

        $this->info("Notificación enviada a {$admins->count()} admin(s) sobre {$count} tienda(s) con SLA vencido.");

        return self::SUCCESS;
    }
}
