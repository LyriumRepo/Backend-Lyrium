<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BirthdayNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;

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
            ->subject('¡Feliz cumpleaños de parte de Lyrium Biomarketplace! 🌱🎂')
            ->view('emails.notifications.birthday', [
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

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '¡Feliz cumpleaños! 🎂',
            'body' => "Queremos desearte un feliz cumpleaños, {$notifiable->name}. ¡Gracias por ser parte de la familia Lyrium! 🌿",
            'data' => [
                'type' => 'birthday',
                'url' => '/customer/profile',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'birthday',
            'subject' => '¡Feliz cumpleaños de parte de la familia Lyrium! 🌿🎂',
        ];
    }
}
