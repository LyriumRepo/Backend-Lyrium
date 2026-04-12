<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispute_number' => $this->dispute_number,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'priority' => $this->priority,
            'description' => $this->description,
            'resolution_notes' => $this->resolution_notes,
            'resolution' => $this->resolution,
            'refund_amount' => $this->refund_amount,
            'assigned_to' => $this->assigned_to,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'order' => $this->whenLoaded('order', fn () => new OrderResource($this->order)),
            'store' => $this->whenLoaded('store', fn () => new StoreResource($this->store)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'assignedTo' => $this->whenLoaded('assignedTo', fn () => new UserResource($this->assignedTo)),
        ];
    }
}
