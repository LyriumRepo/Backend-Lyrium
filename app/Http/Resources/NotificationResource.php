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
            'order_id' => $data['order_id'] ?? null,
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
            'conversation_id' => $data['conversation_id'] ?? null,
            'store_id' => $data['store_id'] ?? null,
            'store_name' => $data['store_name'] ?? null,
            'store_status' => $data['status'] ?? null,
            'seller_name' => $data['seller_name'] ?? null,
            'order_number' => $data['order_number'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'product_status' => $data['status'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'service_name' => $data['service_name'] ?? null,
            'service_status' => $data['service_status'] ?? $data['status'] ?? null,
            'reason' => $data['reason'] ?? null,
            'contract_id' => $data['contract_id'] ?? null,
            'contract_number' => $data['contract_number'] ?? null,
            'contract_name' => $data['contract_name'] ?? null,
            'contract_status' => $data['contract_status'] ?? null,
            'contract_version' => $data['contract_version'] ?? null,
            'contract_action' => $data['contract_action'] ?? null,
            'is_read' => $this->read_at !== null,
        ];
    }
}
