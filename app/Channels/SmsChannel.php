<?php

declare(strict_types=1);

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toSms($notifiable);

        $phone = $notifiable->phone ?? $notifiable->phone_2 ?? null;

        if (!$phone) {
            Log::warning('[SmsChannel] No se pudo enviar SMS: destinatario sin teléfono.', [
                'notifiable' => get_class($notifiable) . '#' . ($notifiable->id ?? '?'),
            ]);
            return;
        }

        Log::info('[SmsChannel] SMS enviado (simulado)', [
            'to' => $phone,
            'message' => $message,
        ]);
    }
}
