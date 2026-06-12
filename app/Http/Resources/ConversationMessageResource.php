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
            'attachments' => $this->whenLoaded('attachments', fn () =>
                $this->attachments->map(fn ($att) => [
                    'id' => (string) $att->id,
                    'file_name' => $att->file_name,
                    'mime_type' => $att->mime_type,
                    'file_size' => $att->file_size,
                    'url' => url('storage/' . $att->file_path),
                    'download_url' => url("api/chat/attachments/{$att->id}/download"),
                ])
            ),
        ];
    }
}
