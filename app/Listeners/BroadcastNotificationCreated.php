<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NotificationCreated;
use Illuminate\Notifications\Events\NotificationSent;

final class BroadcastNotificationCreated
{
    public function handle(NotificationSent $event): void
    {
        // Solo para notificaciones guardadas en base de datos
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;

        // Obtener la última notificación creada para este usuario
        $notification = $notifiable->notifications()->latest()->first();

        if ($notification) {
            broadcast(new NotificationCreated($notification));
        }
    }
}
