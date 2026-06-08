<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Store;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewOrderSellerNotification;
use App\Notifications\StoreStatusNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class TestNotifications extends Command
{
    protected $signature = 'app:test-notifications {user? : Email del usuario a notificar}';

    protected $description = 'Prueba el sistema de notificaciones (DB, email, SMS, Push)';

    public function handle(): int
    {
        $userEmail = $this->argument('user');
        $user = $userEmail
            ? User::where('email', $userEmail)->first()
            : User::first();

        if (!$user) {
            $this->error('No se encontró ningún usuario. Crea uno primero o especifica un email.');
            $this->info('Uso: php artisan app:test-notifications email@ejemplo.com');
            return self::FAILURE;
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  PRUEBA DE NOTIFICACIONES");
        $this->info("  Usuario: {$user->name} <{$user->email}>");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // 1. Verificar tabla notifications
        $this->newLine();
        $this->info("1. Verificando estructura...");
        $this->checkTableStructure();

        // 2. Mostrar configuración actual
        $this->newLine();
        $this->info("2. Configuración actual del usuario:");
        $this->showUserSettings($user);

        // 3. Probar NewOrderSellerNotification
        $this->newLine();
        $this->info("3. Probando NewOrderSellerNotification...");
        $this->testNewOrder($user);

        // 4. Probar StoreStatusNotification
        $this->newLine();
        $this->info("4. Probando StoreStatusNotification...");
        $this->testStoreStatus($user);

        // 5. Probar TicketCreatedNotification
        $this->newLine();
        $this->info("5. Probando TicketCreatedNotification...");
        $this->testTicketCreated($user);

        // 6. Probar TicketStatusChangedNotification
        $this->newLine();
        $this->info("6. Probando TicketStatusChangedNotification...");
        $this->testTicketStatusChanged($user);

        // 7. Mostrar últimos logs
        $this->newLine();
        $this->info("7. Últimos logs del storage/logs/laravel.log:");
        $this->showRecentLogs();

        // 8. Mostrar notificaciones en BD
        $this->newLine();
        $this->info("8. Notificaciones en base de datos:");
        $this->showDatabaseNotifications($user);

        $this->newLine();
        $this->info("✅ PRUEBA COMPLETADA");
        $this->warn("Revisa el archivo storage/logs/laravel.log para ver SMS y Push simulados.");

        return self::SUCCESS;
    }

    private function checkTableStructure(): void
    {
        try {
            $columns = DB::select('SHOW COLUMNS FROM notifications');
            $this->line("   Tabla 'notifications' existe con " . count($columns) . " columnas.");
        } catch (\Exception $e) {
            $this->warn("   Tabla 'notifications' no encontrada. Ejecuta 'php artisan migrate'.");
        }
    }

    private function showUserSettings(User $user): void
    {
        $settings = $user->notificationSetting;

        if ($settings) {
            $this->line("   ┌──────────────────────┬───────┐");
            $this->line("   │ email_order          │ " . ($settings->wantsEmailOrder() ? "✅ ON " : "❌ OFF") . " │");
            $this->line("   │ email_promotions     │ " . ($settings->wantsEmailPromotions() ? "✅ ON " : "❌ OFF") . " │");
            $this->line("   │ email_newsletter     │ " . ($settings->wantsEmailNewsletter() ? "✅ ON " : "❌ OFF") . " │");
            $this->line("   │ push_notifications   │ " . ($settings->wantsPush() ? "✅ ON " : "❌ OFF") . " │");
            $this->line("   └──────────────────────┴───────┘");
        } else {
            $this->line("   ⚠️  Sin registro en user_notification_settings — se usan defaults:");
            $this->line("   email_order=ON, email_promotions=ON, email_newsletter=OFF, push=ON");
        }
    }

    private function testNewOrder(User $user): void
    {
        $store = Store::first();
        if (!$store) {
            $this->warn("   ⚠️  No hay tiendas — se omite esta prueba");
            return;
        }

        $order = Order::first();
        if (!$order) {
            $this->warn("   ⚠️  No hay órdenes — se omite esta prueba");
            return;
        }

        Log::info('=== [TEST] Inicio NewOrderSellerNotification ===');
        Notification::sendNow($user, new NewOrderSellerNotification($order, $store));
        Log::info('=== [TEST] Fin NewOrderSellerNotification ===');

        $this->line("   ✅ Notificación de nuevo pedido enviada a {$user->name}");
        $this->line("   → Revisa storage/logs/laravel.log para SMS/Push simulados");
    }

    private function testStoreStatus(User $user): void
    {
        $store = Store::first();
        if (!$store) {
            $this->warn("   ⚠️  No hay tiendas — se omite esta prueba");
            return;
        }

        Log::info('=== [TEST] Inicio StoreStatusNotification ===');
        Notification::sendNow($user, new StoreStatusNotification($store, 'approved'));
        Log::info('=== [TEST] Fin StoreStatusNotification ===');

        $this->line("   ✅ Notificación de cambio de estado enviada");
    }

    private function testTicketCreated(User $user): void
    {
        $ticket = Ticket::first();
        if (!$ticket) {
            $this->warn("   ⚠️  No hay tickets — se omite esta prueba");
            return;
        }

        Log::info('=== [TEST] Inicio TicketCreatedNotification ===');
        Notification::sendNow($user, new TicketCreatedNotification($ticket));
        Log::info('=== [TEST] Fin TicketCreatedNotification ===');

        $this->line("   ✅ Notificación de nuevo ticket enviada");
    }

    private function testTicketStatusChanged(User $user): void
    {
        $ticket = Ticket::first();
        if (!$ticket) {
            $this->warn("   ⚠️  No hay tickets — se omite esta prueba");
            return;
        }

        Log::info('=== [TEST] Inicio TicketStatusChangedNotification ===');
        Notification::sendNow($user, new TicketStatusChangedNotification($ticket, 'open', 'in_progress'));
        Log::info('=== [TEST] Fin TicketStatusChangedNotification ===');

        $this->line("   ✅ Notificación de cambio de estado de ticket enviada");
    }

    private function showRecentLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            $this->warn("   ⚠️  No se encontró storage/logs/laravel.log");
            return;
        }

        $lines = file($logPath);
        $relevantLines = array_filter($lines, fn($line) =>
            str_contains($line, '[SmsChannel]') ||
            str_contains($line, '[PushChannel]') ||
            str_contains($line, '[TEST]')
        );

        $relevantLines = array_slice(array_values($relevantLines), -8);

        if (empty($relevantLines)) {
            $this->line("   (sin logs de notificaciones aún)");
        } else {
            foreach ($relevantLines as $line) {
                $clean = trim(preg_replace('/^\[.*?\]\s+local\.\w+:\s+/', '✓ ', $line));
                $this->line("   {$clean}");
            }
        }
    }

    private function showDatabaseNotifications(User $user): void
    {
        $notifications = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($notifications->isEmpty()) {
            $this->line("   (sin notificaciones en BD para este usuario)");
            return;
        }

        foreach ($notifications as $n) {
            $data = json_decode($n->data, true);
            $type = $data['type'] ?? 'desconocido';
            $this->line("   📌 {$n->id}: {$type} — {$n->created_at}");
        }
    }
}
