<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NotificationCreated;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

final class BroadcastNotificationCreated
{
    public function handle(NotificationSent $event): void
    {
        // Solo para notificaciones guardadas en base de datos
        if ($event->channel !== 'database') {
            return;
        }

        $notification = $event->response;

        if ($notification) {
            try {
                broadcast(new NotificationCreated($notification));
            } catch (\Throwable $e) {
                Log::warning('[Notifications] broadcast NotificationCreated failed (Reverb down?): ' . $e->getMessage());
            }
        }
    }
}
