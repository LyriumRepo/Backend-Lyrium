<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'collection_name' => $this->collection_name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->size,
            'url' => $this->getUrl(),
            'thumbnail_url' => $this->getUrl('thumb'),
            'medium_url' => $this->getUrl('medium'),
            'large_url' => $this->getUrl('large'),
            'responsive_images' => $this->getResponsiveImageUrls(),
            'custom_properties' => $this->custom_properties,
            'order' => $this->order_column,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
