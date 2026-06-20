<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UserBannedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Tu cuenta ha sido suspendida - Lyrium BioMarketplace')
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Tu cuenta en Lyrium BioMarketplace ha sido suspendida.');

        if ($this->reason) {
            $mail->line('Motivo: ' . $this->reason);
        }

        return $mail->line('Si crees que esto es un error, puedes contactarnos.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reason' => $this->reason,
            'type' => 'user_banned',
        ];
    }
}
