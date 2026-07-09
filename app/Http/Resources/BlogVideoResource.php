<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogVideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'category' => $this->category,
            'category_label' => $this->category_label,
            'platform' => $this->platform ?? 'youtube',
            'url' => $this->url,
            'youtube_id' => $this->youtube_id,
            'thumbnail' => $this->thumbnail,
            'duration' => $this->duration,
            'published_at' => $this->published_at?->toDateString(),
        ];
    }
}
