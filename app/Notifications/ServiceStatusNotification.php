<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ServiceStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Service $service,
        private readonly string $newStatus,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storeName = $this->service->store?->trade_name ?? 'tu tienda';

        return (new MailMessage)
            ->subject(match ($this->newStatus) {
                'approved' => '✅ Tu servicio fue aprobado — Lyrium BioMarketplace',
                'rejected' => '❌ Tu servicio no fue aprobado — Lyrium BioMarketplace',
                default    => 'Actualización de tu servicio — Lyrium BioMarketplace',
            })
            ->view('emails.notifications.service-status', [
                'name'        => $notifiable->name,
                'serviceName' => $this->service->name,
                'storeName'   => $storeName,
                'status'      => $this->newStatus,
                'reason'      => $this->reason,
                'actionUrl'   => config('app.frontend_url') . '/seller/services',
            ]);
    }

    public function toPush(object $notifiable): array
    {
        $label = $this->newStatus === 'approved' ? 'aprobado ✅' : 'rechazado ❌';
        return [
            'title' => 'Servicio ' . $label,
            'body'  => "Tu servicio \"{$this->service->name}\" fue {$label}." .
                       ($this->reason ? " Motivo: {$this->reason}" : ''),
            'data'  => [
                'type'       => 'service_status_changed',
                'service_id' => (string) $this->service->id,
                'status'     => $this->newStatus,
                'url'        => '/seller/services',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->newStatus === 'approved' ? 'aprobado' : 'rechazado';
        return [
            'type'         => 'service_status_changed',
            'service_id'   => $this->service->id,
            'service_name' => $this->service->name,
            'status'       => $this->newStatus,
            'reason'       => $this->reason,
            'subject'      => "Tu servicio \"{$this->service->name}\" fue {$label}" .
                              ($this->reason ? " — {$this->reason}" : ''),
        ];
    }
}
