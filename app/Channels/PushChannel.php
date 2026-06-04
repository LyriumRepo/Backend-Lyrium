<?php

declare(strict_types=1);

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $title = $notification->toPush($notifiable)['title'] ?? 'Notificación';
        $body = $notification->toPush($notifiable)['body'] ?? '';

        Log::info('[PushChannel] Notificación push enviada (simulada)', [
            'user_id' => $notifiable->id ?? '?',
            'title' => $title,
            'body' => $body,
        ]);
    }
}
