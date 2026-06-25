<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\PlanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PlanRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(private readonly PlanRequest $planRequest) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $planName  = $this->planRequest->plan?->name ?? 'Plan';
        $storeName = $this->planRequest->store?->trade_name ?? 'tu tienda';

        return [
            'title' => 'Solicitud de plan rechazada',
            'body'  => "Tu solicitud del plan {$planName} para {$storeName} fue rechazada.",
            'data'  => [
                'type'            => 'plan_rejected',
                'plan_request_id' => $this->planRequest->id,
                'url'             => '/seller/planes',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName  = $this->planRequest->plan?->name ?? 'Plan';
        $storeName = $this->planRequest->store?->trade_name ?? 'tu tienda';
        $notes     = $this->planRequest->admin_notes ?? '';
        $actionUrl = config('app.frontend_url') . '/seller/planes';

        return (new MailMessage)
            ->subject("Solicitud de plan rechazada — {$planName}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("Tu solicitud para activar el plan **{$planName}** en la tienda **{$storeName}** fue rechazada por el equipo de Lyrium.")
            ->line("**Motivo:** {$notes}")
            ->action('Ver mis planes', $actionUrl)
            ->line('Si tienes preguntas, contáctanos en soporte@lyrium.pe.');
    }

    public function toArray(object $notifiable): array
    {
        $planName  = $this->planRequest->plan?->name ?? 'Plan';
        $storeName = $this->planRequest->store?->trade_name ?? 'tu tienda';
        $notes     = $this->planRequest->admin_notes ?? '';

        return [
            'type'            => 'plan_rejected',
            'title'           => "Solicitud de plan rechazada",
            'message'         => "Tu solicitud del plan {$planName} para {$storeName} fue rechazada. Motivo: {$notes}",
            'plan_request_id' => $this->planRequest->id,
            'plan_name'       => $planName,
            'store_name'      => $storeName,
            'admin_notes'     => $notes,
            'action_url'      => '/seller/planes',
        ];
    }
}
