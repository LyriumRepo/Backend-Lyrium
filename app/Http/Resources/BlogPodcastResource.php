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
            'image' => $this->image,
            'audio_url' => $this->audio_url,
            'duration' => $this->duration,
            'published_at' => $this->published_at?->toDateString(),
        ];
    }
}
