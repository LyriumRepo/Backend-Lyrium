<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ServicePendingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Service $service,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🆕 Nuevo servicio pendiente de revisión — Lyrium BioMarketplace')
            ->view('emails.notifications.service-pending-review', [
                'adminName'   => $notifiable->name,
                'serviceName' => $this->service->name,
                'storeName'   => $this->service->store?->trade_name ?? 'Tienda desconocida',
                'price'       => $this->service->price,
                'actionUrl'   => config('app.frontend_url') . '/admin/services',
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

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'service_pending_review',
            'title'        => "🆕 Servicio pendiente: {$this->service->name}",
            'message'      => "La tienda \"{$this->service->store?->trade_name ?? 'desconocida'}\" registró el servicio \"{$this->service->name}\" y está esperando aprobación.",
            'service_id'   => $this->service->id,
            'service_name' => $this->service->name,
            'store_id'     => $this->service->store_id,
            'store_name'   => $this->service->store?->trade_name ?? '',
            'action_url'   => '/admin/services',
        ];
    }
}
