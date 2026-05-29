<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\ConversationMessageResource;
use App\Models\ConversationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NewConversationMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ConversationMessage $message,
        public readonly int $customerUserId,
        public readonly int $storeId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->customerUserId),
            new PrivateChannel('store.'.$this->storeId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => new ConversationMessageResource(
                $this->message->loadMissing(['sender', 'conversation'])
            ),
            'conversation_id' => (string) $this->message->conversation_id,
        ];
    }
}
