<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'status' => $this->status,
            'sticker' => $this->sticker,
            'price' => (float) ($this->sale_price ?? $this->price),
            'regular_price' => (float) ($this->regular_price ?? $this->price),
            'stock' => (int) $this->stock,
            'images' => $this->resource->getMedia('images')->map(fn ($media) => [
                'src' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'medium' => $media->getUrl('medium'),
                'large' => $media->getUrl('large'),
                'alt' => $this->name,
            ])->values()->all(),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($cat) => [
                'name' => $cat->name,
                'slug' => $cat->slug,
            ])->values()->all()),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->store_name,
                'slug' => $this->store->slug,
                'logo' => $this->store->logo,
                'email' => $this->store->corporate_email,
                'phone' => $this->store->phone,
            ]),
            'rating' => [
                'average' => $this->average_rating,
                'count' => $this->review_count,
            ],
        ];

        if ($this->type === 'physical') {
            $data['weight'] = $this->weight ? (float) $this->weight : null;
            $data['dimensions'] = $this->dimensions;
            $data['expirationDate'] = $this->expiration_date?->toDateString();
        }

        if ($this->type === 'digital') {
            $data['downloadUrl'] = $this->download_url;
            $data['downloadLimit'] = $this->download_limit;
            $data['fileType'] = $this->file_type;
            $data['fileSize'] = $this->file_size;
        }

        if ($this->type === 'service') {
            $data['serviceDuration'] = $this->service_duration;
            $data['serviceModality'] = $this->service_modality;
            $data['serviceLocation'] = $this->service_location;
        }

        // Include attributes
        $data['mainAttributes'] = $this->whenLoaded('mainAttributes', fn () => $this->mainAttributes->map(fn ($attr) => [
            'values' => $attr->values,
        ])->values()->all()
        );
        $data['additionalAttributes'] = $this->whenLoaded('additionalAttributes', fn () => $this->additionalAttributes->map(fn ($attr) => [
            'values' => $attr->values,
        ])->values()->all()
        );

        return $data;
    }
}
