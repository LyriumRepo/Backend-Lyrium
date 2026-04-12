<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\TicketMessageResource;
use App\Models\TicketMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly TicketMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('ticket.'.$this->message->ticket_id)];
    }

    public function broadcastWith(): array
    {
        return ['message' => new TicketMessageResource($this->message->loadMissing('user', 'attachments'))];
    }
}
