<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'quantity' => $this->quantity,
            'condition' => $this->condition,
            'notes' => $this->notes,
            'order_item' => $this->whenLoaded('orderItem', fn () => [
                'id' => $this->orderItem->id,
                'product_name' => $this->orderItem->product_name ?? $this->orderItem->product?->name,
                'quantity' => $this->orderItem->quantity,
                'price' => (float) $this->orderItem->price,
            ]),
        ];
    }
}
