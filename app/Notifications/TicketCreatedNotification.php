<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        if (!app()->environment('local') && ($settings?->wantsEmailOrder() ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nuevo ticket: {$this->ticket->ticket_number}")
            ->greeting('Nuevo ticket de soporte')
            ->line("**Asunto:** {$this->ticket->subject}")
            ->line("**Categoría:** {$this->ticket->category}")
            ->line("**Prioridad:** {$this->ticket->priority}")
            ->line("**Vendedor:** {$this->ticket->user->name}")
            ->line("**Tienda:** {$this->ticket->store->trade_name}")
            ->action('Ver ticket', config('app.frontend_url', 'http://localhost:3000').'/admin/helpdesk');
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'Nuevo ticket de soporte',
            'body' => "{$this->ticket->subject} — Prioridad: {$this->ticket->priority}",
            'data' => [
                'type' => 'ticket_created',
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
            'category' => $this->ticket->category,
            'priority' => $this->ticket->priority,
            'vendor_name' => $this->ticket->user->name,
            'type' => 'ticket_created',
        ];
    }
}
