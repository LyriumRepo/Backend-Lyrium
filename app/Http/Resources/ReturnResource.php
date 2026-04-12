<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,
            'status' => $this->status,
            'reason' => $this->reason,
            'reason_details' => $this->reason_details,
            'resolution_notes' => $this->when(
                $request->user()?->hasRole('seller', 'administrator') || $request->user()?->id === $this->user_id,
                $this->resolution_notes
            ),
            'refund_amount' => $this->refund_amount ? (float) $this->refund_amount : null,
            'refund_method' => $this->refund_method,
            'shipping_carrier' => $this->shipping_carrier,
            'tracking_number' => $this->tracking_number,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->trade_name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => ReturnItemResource::collection($this->items)),
            'can_cancel' => $this->when($request->user(), fn () => $this->canCancel()),
            'can_approve' => $this->when($request->user()?->hasRole('seller', 'administrator'), fn () => $this->canApprove()),
            'can_reject' => $this->when($request->user()?->hasRole('seller', 'administrator'), fn () => $this->canReject()),
            'can_mark_received' => $this->when($request->user()?->hasRole('seller', 'administrator'), fn () => $this->canMarkReceived()),
            'can_refund' => $this->when($request->user()?->hasRole('seller', 'administrator'), fn () => $this->canRefund()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
