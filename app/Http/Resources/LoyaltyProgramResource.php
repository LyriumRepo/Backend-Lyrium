<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LoyaltyProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'points_per_currency' => $this->points_per_currency,
            'currency_per_point' => $this->currency_per_point,
            'min_points_to_redeem' => $this->min_points_to_redeem,
            'created_at' => $this->created_at?->toIso8601String(),
            'tiers' => $this->whenLoaded('tiers', fn () => LoyaltyTierResource::collection($this->tiers)),
            'rewards' => $this->whenLoaded('rewards', fn () => LoyaltyRewardResource::collection($this->rewards)),
        ];
    }
}
