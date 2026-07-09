<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogShortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'platform' => $this->platform,
            'url' => $this->url,
            'thumbnail' => $this->thumbnail,
            'duration' => $this->duration,
            'metadata' => $this->metadata,
            'published_at' => $this->published_at?->toDateString(),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->store_name ?? $this->store->trade_name ?? '',
                'slug' => $this->store->slug ?? '',
            ]),
        ];
    }
}
