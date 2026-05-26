<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConversationMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isCustomer = $this->sender_id === $this->conversation?->customer_user_id;

        return [
            'id' => (string) $this->id,
            'conversation_id' => (string) $this->conversation_id,
            'sender_id' => (string) $this->sender_id,
            'sender_name' => $this->sender?->name ?? '',
            'sender_type' => $isCustomer ? 'customer' : 'seller',
            'content' => $this->content,
            'timestamp' => $this->created_at?->toIso8601String(),
            'read' => $this->read_at !== null,
        ];
    }
}
