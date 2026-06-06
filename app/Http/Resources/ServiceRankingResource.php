<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ServiceRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (float) ($this->price ?? 0),
            'image' => $this->image,
            'duration_minutes' => (int) ($this->duration_minutes ?? 0),
            'is_home_service' => (bool) ($this->is_home_service ?? false),
            'rating_average' => round((float) ($this->rating_average ?? 0), 1),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => (string) $this->store->id,
                'name' => $this->store->store_name,
                'slug' => $this->store->slug,
                'logo' => $this->store->logo,
            ]),
        ];
    }
}
