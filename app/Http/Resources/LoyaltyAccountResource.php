<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LoyaltyAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'program_id' => $this->program_id,
            'tier_id' => $this->tier_id,
            'points_balance' => $this->points_balance,
            'lifetime_points' => $this->lifetime_points,
            'points_redeemed' => $this->points_redeemed,
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'tier' => $this->whenLoaded('tier', fn () => new LoyaltyTierResource($this->tier)),
            'program' => $this->whenLoaded('program', fn () => new LoyaltyProgramResource($this->program)),
        ];
    }
}
