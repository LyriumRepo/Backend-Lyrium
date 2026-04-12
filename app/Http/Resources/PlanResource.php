<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'monthly_fee' => $this->monthly_fee,
            'commission_rate' => $this->commission_rate,
            'has_membership_fee' => $this->has_membership_fee,
            'features' => $this->features,
            'detailed_benefits' => $this->detailed_benefits,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
