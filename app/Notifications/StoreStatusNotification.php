<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class StoreStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Store $store,
        private readonly string $newStatus,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $greeting = match ($this->newStatus) {
            'approved' => '!Felicidades, ' . $notifiable->name . '!',
            'rejected', 'banned' => 'Hola, ' . $notifiable->name,
            default => 'Hola, ' . $notifiable->name,
        };

        return (new MailMessage)
            ->subject(match ($this->newStatus) {
                'approved' => '!Tu tienda ha sido aprobada! - Lyrium BioMarketplace',
                'rejected' => 'Actualizacion sobre tu tienda - Lyrium BioMarketplace',
                'banned' => 'Tu tienda ha sido suspendida - Lyrium BioMarketplace',
                default => 'Actualizacion de tu tienda - Lyrium BioMarketplace',
            })
            ->view('emails.notifications.store-status', [
                'greeting' => $greeting,
                'storeName' => $this->store->trade_name,
                'status' => $this->newStatus,
                'reason' => $this->reason,
                'actionUrl' => config('app.frontend_url') . '/seller/settings',
                'showTagline' => true,
            ])
            ->withSymfonyMessage(function ($message) {
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
        $statusLabels = [
            'approved' => 'aprobada',
            'rejected' => 'rechazada',
            'banned' => 'suspendida',
            'pending' => 'pendiente',
        ];

        $label = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return [
            'title' => 'Estado de tienda actualizado',
            'body' => "Tu tienda \"{$this->store->trade_name}\" ha sido {$label}." . ($this->reason ? " Motivo: {$this->reason}" : ''),
            'data' => [
                'type' => 'store_status_changed',
                'store_id' => $this->store->id,
                'status' => $this->newStatus,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'approved' => 'aprobada',
            'rejected' => 'rechazada',
            'banned' => 'suspendida',
            'pending' => 'pendiente',
        ];

        $label = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return [
            'store_id' => $this->store->id,
            'store_name' => $this->store->trade_name,
            'status' => $this->newStatus,
            'reason' => $this->reason,
            'type' => 'store_status_changed',
            'subject' => "Tu tienda \"{$this->store->trade_name}\" ha sido {$label}" . ($this->reason ? " - {$this->reason}" : ''),
        ];
    }
}
