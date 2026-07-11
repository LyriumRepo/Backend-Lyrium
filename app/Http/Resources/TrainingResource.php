<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TrainingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'url' => $this->url,
            'platform' => $this->platform ?? 'other',
            'thumbnail' => $this->thumbnail,
            'category' => $this->category,
            'sort_order' => $this->sort_order,
            'is_required' => $this->is_required,
            'is_published' => $this->is_published,
            'completed' => $this->completed ?? false,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
