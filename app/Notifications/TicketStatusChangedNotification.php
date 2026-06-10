<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $oldStatus,
        private readonly string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;

        if (!app()->environment('local') && ($settings?->wantsEmailOrder() ?? true)) {
            $channels[] = 'mail';
        }

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'open' => 'Abierto',
            'in_progress' => 'En Proceso',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
        ];

        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} — Estado actualizado")
            ->greeting('Actualización de tu ticket')
            ->line("**Ticket:** {$this->ticket->ticket_number}")
            ->line("**Asunto:** {$this->ticket->subject}")
            ->line('**Estado anterior:** '.($statusLabels[$this->oldStatus] ?? $this->oldStatus))
            ->line('**Nuevo estado:** '.($statusLabels[$this->newStatus] ?? $this->newStatus))
            ->action('Ver ticket', config('app.frontend_url', 'http://localhost:3000').'/seller/help');
    }

    public function toPush(object $notifiable): array
    {
        $statusLabels = [
            'open' => 'Abierto',
            'in_progress' => 'En Proceso',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
        ];

        $statusText = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return [
            'title' => 'Estado de ticket actualizado',
            'body' => "Ticket {$this->ticket->ticket_number}: {$statusText}",
            'data' => [
                'type' => 'ticket_status_changed',
                'ticket_id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'type' => 'ticket_status_changed',
        ];
    }
}
