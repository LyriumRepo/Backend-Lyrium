<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VendedorPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store_name' => $this->store->trade_name,
            'store_slug' => $this->store->slug,
            'store_status' => $this->store->status,
            'seller_name' => $this->store->owner?->name,
            'seller_email' => $this->store->owner?->email,
            'plan' => $this->plan ? [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
                'monthly_fee' => $this->plan->monthly_fee,
                'css_color' => $this->plan->css_color,
            ] : null,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
