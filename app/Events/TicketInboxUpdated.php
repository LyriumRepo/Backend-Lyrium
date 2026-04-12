<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $notifyUserId,
        public readonly int $ticketId,
        public readonly int $unreadCount,
        public readonly string $previewText,
        public readonly int $totalMessages,
        public readonly string $updatedAt,
    ) {}

    public function broadcastOn(): array|Channel
    {
        return new PrivateChannel("user.{$this->notifyUserId}");
    }

    public function broadcastAs(): string
    {
        return 'TicketInboxUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'unread_count' => $this->unreadCount,
            'preview_text' => $this->previewText,
            'total_messages' => $this->totalMessages,
            'updated_at' => $this->updatedAt,
        ];
    }
}
