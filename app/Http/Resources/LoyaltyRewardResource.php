<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LoyaltyRewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'name' => $this->name,
            'description' => $this->description,
            'reward_type' => $this->reward_type,
            'type_label' => $this->getTypeLabel(),
            'value' => $this->value,
            'points_required' => $this->points_required,
            'max_uses' => $this->max_uses,
            'uses_count' => $this->uses_count,
            'is_active' => $this->is_active,
            'is_available' => $this->isAvailable(),
            'valid_from' => $this->valid_from?->toIso8601String(),
            'valid_until' => $this->valid_until?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
