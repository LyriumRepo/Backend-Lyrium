<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserRedeemedRewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'reward_id' => $this->reward_id,
            'code' => $this->code,
            'discount_value' => $this->discount_value,
            'is_used' => $this->is_used,
            'used_at' => $this->used_at?->toIso8601String(),
            'valid_until' => $this->valid_until?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_valid' => $this->isValid(),
            'reward' => $this->whenLoaded('reward', fn () => new LoyaltyRewardResource($this->reward)),
        ];
    }
}
