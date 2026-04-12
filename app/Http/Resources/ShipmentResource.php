<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'store_id' => $this->store_id,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'carrier' => $this->carrier,
            'status' => $this->status,
            'notes' => $this->when(
                $request->user()?->hasRole('seller', 'administrator'),
                $this->notes
            ),
            'events' => $this->events,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'order' => $this->whenLoaded('order', fn () => [
                'order_number' => $this->order->order_number,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->trade_name,
            ]),
            'shipping_method' => $this->whenLoaded('shippingMethod', fn () => [
                'id' => $this->shippingMethod->id,
                'name' => $this->shippingMethod->name,
                'code' => $this->shippingMethod->code,
            ]),
        ];
    }
}
