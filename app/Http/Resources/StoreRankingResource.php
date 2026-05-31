<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StoreRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->store_name,
            'slug' => $this->slug,
            'logo' => $this->logo,
            'rating_average' => round((float) ($this->average_rating ?? 0), 1),
            'review_count' => (int) ($this->review_count ?? 0),
        ];
    }
}
