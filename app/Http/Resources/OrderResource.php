<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canCancel = $this->resource->isPendingSeller();
        $canConfirm = $this->resource->canBeConfirmedBySeller();
        $canUpdate = $this->resource->canBeUpdatedBySeller();

        return [
            'id' => (string) $this->id,
            'orderNumber' => $this->order_number,
            'status' => $this->status,
            'globalStatus' => $this->computeGlobalStatus(),
            'statusLabel' => $this->getStatusLabel(),
            'paymentMethod' => $this->payment_method,
            'paymentStatus' => $this->payment_status,
            'shipping' => [
                'name' => $this->shipping_name,
                'email' => $this->shipping_email,
                'phone' => $this->shipping_phone,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'postalCode' => $this->shipping_postal_code,
                'notes' => $this->shipping_notes,
            ],
            'subtotal' => (float) $this->subtotal,
            'shippingCost' => (float) $this->shipping_cost,
            'taxAmount' => (float) $this->tax_amount,
            'discountAmount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'couponCode' => $this->coupon_code,
            'notes' => $this->notes,
            'actions' => [
                'canCancel' => $canCancel,
                'canConfirm' => $canConfirm,
                'canUpdate' => $canUpdate,
            ],
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)->resolve($request)),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => (string) $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
