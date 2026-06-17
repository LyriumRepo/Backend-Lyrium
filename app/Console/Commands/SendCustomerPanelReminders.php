<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\CustomerPanelReminderMail;
use App\Models\CustomerPanelReminder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendCustomerPanelReminders extends Command
{
    protected $signature = 'customers:panel-reminders';

    protected $description = 'Envía recordatorios por correo a clientes que compraron pero aún no exploraron su panel';

    private const THRESHOLDS = [7, 30, 90];

    public function handle(): int
    {
        $customers = User::role('customer')
            ->whereNull('panel_visited_at')
            ->whereNotNull('email_verified_at')
            ->where('is_banned', false)
            ->whereHas('orders')
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No hay clientes elegibles para recordatorio hoy.');
            return self::SUCCESS;
        }

        $this->info("Revisando {$customers->count()} cliente(s)...");

        $sent = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $firstOrder = Order::where('user_id', $customer->id)
                ->orderBy('created_at')
                ->value('created_at');

            if (!$firstOrder) {
                continue;
            }

            $daysSince = (int) now()->diffInDays($firstOrder);

            foreach (self::THRESHOLDS as $index => $days) {
                if ($daysSince < $days) {
                    break; // Not reached this threshold yet
                }

                $alreadySent = CustomerPanelReminder::where('user_id', $customer->id)
                    ->where('reminder_day', $days)
                    ->exists();

                if ($alreadySent) {
                    continue; // Try next threshold
                }

                // Send the next pending reminder (one per run)
                try {
                    Mail::to($customer->email)->send(
                        new CustomerPanelReminderMail($customer, $index + 1)
                    );

                    CustomerPanelReminder::create([
                        'user_id' => $customer->id,
                        'reminder_day' => $days,
                    ]);

                    $sent++;
                    $this->line("  ✓ {$customer->name} ({$customer->email}) — recordatorio " . ($index + 1) . " (día {$days})");
                } catch (\Throwable $e) {
                    Log::error('[PanelReminder] Error al enviar recordatorio', [
                        'user_id' => $customer->id,
                        'email' => $customer->email,
                        'reminder_day' => $days,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  ✗ {$customer->name}: {$e->getMessage()}");
                }

                break; // Only one reminder per customer per run
            }
        }

        $this->info("Recordatorios enviados: {$sent} | Omitidos (ya recibieron todos o no aplican): " . ($customers->count() - $sent));

        return self::SUCCESS;
    }
}
