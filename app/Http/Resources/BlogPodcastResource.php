<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogPodcastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'type' => $this->type ?? 'audio',
            'platform' => $this->platform,
            'url' => $this->url,
            'cover_image' => $this->cover_image ?? $this->image,
            'thumbnail' => $this->thumbnail,
            'audio_url' => $this->audio_url,
            'duration' => $this->duration,
            'tags' => $this->tags,
            'published_at' => $this->published_at?->toDateString(),
        ];
    }
}
