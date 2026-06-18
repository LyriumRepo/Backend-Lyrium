<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PlanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewPlanRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(private readonly PlanRequest $planRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storeName  = $this->planRequest->store?->trade_name ?? 'Tienda';
        $planName   = $this->planRequest->plan?->name ?? 'Plan';
        $amount     = number_format((float) $this->planRequest->total_amount, 2);
        $months     = $this->planRequest->months;
        $actionUrl  = config('app.frontend_url') . '/admin/planes';

        return (new MailMessage)
            ->subject("💳 Nueva solicitud de plan — {$storeName}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("La tienda **{$storeName}** ha enviado una solicitud de pago del plan **{$planName}**.")
            ->line("**Monto:** S/ {$amount} · **Período:** {$months} " . ($months === 1 ? 'mes' : 'meses'))
            ->action('Ver solicitud', $actionUrl)
            ->line('Revisa y aprueba la solicitud desde el panel de administración.');
    }

    public function toArray(object $notifiable): array
    {
        $storeName = $this->planRequest->store?->trade_name ?? 'Tienda';
        $planName  = $this->planRequest->plan?->name ?? 'Plan';
        $amount    = number_format((float) $this->planRequest->total_amount, 2);
        $months    = $this->planRequest->months;

        return [
            'type'             => 'new_plan_request',
            'title'            => "💳 Nueva solicitud de plan — {$storeName}",
            'message'          => "{$storeName} solicita activar el plan {$planName} por {$months} " . ($months === 1 ? 'mes' : 'meses') . " (S/ {$amount}). Pendiente de revisión.",
            'plan_request_id'  => $this->planRequest->id,
            'store_id'         => $this->planRequest->store_id,
            'store_name'       => $storeName,
            'plan_name'        => $planName,
            'amount'           => $this->planRequest->total_amount,
            'months'           => $months,
            'action_url'       => '/admin/planes',
        ];
    }
}
