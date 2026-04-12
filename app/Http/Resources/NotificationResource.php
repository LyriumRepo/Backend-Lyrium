<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'ticket_id' => $data['ticket_id'] ?? null,
            'ticket_number' => $data['ticket_number'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message_preview' => $data['message_preview'] ?? null,
            'sender_name' => $data['sender_name'] ?? null,
            'old_status' => $data['old_status'] ?? null,
            'new_status' => $data['new_status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'category' => $data['category'] ?? null,
            'vendor_name' => $data['vendor_name'] ?? null,
            'is_read' => $this->read_at !== null,
        ];
    }
}
