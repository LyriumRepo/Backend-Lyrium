<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'typeLabel' => $this->type === 'percentage' ? 'Porcentaje' : 'Monto fijo',
            'value' => (float) $this->value,
            'minOrderAmount' => $this->min_order_amount ? (float) $this->min_order_amount : null,
            'maxDiscount' => $this->max_discount ? (float) $this->max_discount : null,
            'usageLimit' => $this->usage_limit,
            'usageCount' => $this->usage_count,
            'perUserLimit' => $this->per_user_limit,
            'isGlobal' => $this->is_global,
            'startsAt' => $this->starts_at?->toIso8601String(),
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'isActive' => $this->is_active,
            'isValid' => $this->isValid(),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => (string) $this->store->id,
                'name' => $this->store->store_name,
                'slug' => $this->store->slug,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
