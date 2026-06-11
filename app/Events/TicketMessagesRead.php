<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $ticketId,
        public readonly int $readByUserId,
    ) {}

    public function broadcastOn(): array|Channel
    {
        return new PrivateChannel("ticket.{$this->ticketId}");
    }

    public function broadcastAs(): string
    {
        return 'TicketMessagesRead';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'read_by_user_id' => $this->readByUserId,
        ];
    }
}
