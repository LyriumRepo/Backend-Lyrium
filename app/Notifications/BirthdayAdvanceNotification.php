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

        if (!($settings?->wantsEmailPromotions() ?? true)) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Tu cumpleaños se acerca! Un regalo de Lyrium te espera 🌿🎁')
            ->view('emails.notifications.birthday-advance', [
                'name' => $notifiable->name,
            ])
            ->withSymfonyMessage(function ($message): void {
                $iconPath = public_path('images/iconologo.png');
                $textPath = public_path('images/nombrelogo.png');
                if (file_exists($iconPath)) {
                    $message->embedFromPath($iconPath, 'logo-icon');
                }
                if (file_exists($textPath)) {
                    $message->embedFromPath($textPath, 'logo-text');
                }
            });
    }
}
