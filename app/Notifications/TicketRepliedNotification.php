<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class TicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly TicketMessage $message
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '💬 Nueva respuesta en tu ticket',
            'body' => mb_strimwidth($this->message->content, 0, 80, '…'),
            'data' => [
                'type' => 'ticket_replied',
                'ticket_id' => (string) $this->ticket->id,
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
            'message_preview' => str($this->message->content)->limit(100)->toString(),
            'sender_name' => $this->message->user->name,
            'type' => 'ticket_replied',
        ];
    }
}
