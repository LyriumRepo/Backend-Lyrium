<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ShippingMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'base_cost' => (float) $this->base_cost,
            'free_shipping_min' => $this->free_shipping_min ? (float) $this->free_shipping_min : null,
            'estimated_days' => $this->estimated_days,
            'allows_tracking' => $this->allows_tracking,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
