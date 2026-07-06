<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BirthdayAdvanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        $settings = $notifiable->notificationSetting;
        $channels = ['database'];

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }
        if ($settings?->wantsEmailPromotions() ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Tu cumpleaños se acerca! Un regalo de Lyrium te espera 🌿🎁')
            ->view('emails.notifications.birthday-advance', [
                'name' => $notifiable->name,
            ]);
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '🎁 ¡Tu cumpleaños se acerca!',
            'body'  => "Hola {$notifiable->name}, en Lyrium estamos preparando algo especial para ti. ¡Faltan solo 14 días! 🌿",
            'data'  => [
                'type' => 'birthday_advance',
                'url'  => '/customer/profile',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'birthday_advance',
            'subject' => '¡Tu cumpleaños se acerca! Un regalo de Lyrium te espera 🌿🎁',
        ];
    }
}
