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
            'short_description' => $this->short_description,
            'status' => $this->status,
            'sticker' => $this->sticker,
            'sku' => $this->sku,
            'price' => (float) ($this->sale_price ?? $this->price),
            'regular_price' => (float) (
                $this->regular_price
                ?? ($this->discount_percentage && $this->discount_percentage < 100
                    ? round($this->price / (1 - $this->discount_percentage / 100), 2)
                    : $this->price)
            ),
            'discount_percentage' => $this->discount_percentage
                ? (float) $this->discount_percentage
                : null,
            'stock' => (int) $this->stock,
            'in_stock' => $this->stock > 0,
            'image' => $this->image,
            'images' => $this->resource->getMedia('images')->map(fn ($m) => [
                'src' => $m->getUrl(),
                'thumb' => $m->getUrl('thumb'),
                'medium' => $m->getUrl('medium'),
                'large' => $m->getUrl('large'),
                'alt' => $this->name,
            ])->values()->all(),

            'categories' => $this->whenLoaded(
                'categories',
                fn () => $this->categories->map(fn ($cat) => [
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ])->values()->all()
            ),

            'store' => $this->whenLoaded('store', fn () => [
                'id' => (string) $this->store->id,
                'name' => $this->store->store_name,
                'slug' => $this->store->slug,
                'logo' => $this->store->getMediaUrl('logo'),
                'logo_marketplace' => $this->store->getMediaUrl('logo_marketplace'),
                'email' => $this->store->corporate_email,
                'phone' => $this->store->phone,
            ]),

            'rating' => [
                'average' => (float) ($this->reviews_avg ?? $this->average_rating),
                'count' => (int) ($this->reviews_count ?? $this->review_count),
            ],

            // Ficha de características — renderizable como tabla key/value
            'characteristics' => $this->whenLoaded(
                'mainAttributes',
                fn () => $this->mainAttributes
                    ->map(fn ($attr) => [
                        'label' => $attr->values['label'] ?? null,
                        'value' => $attr->values['value'] ?? null,
                    ])
                    ->filter(fn ($item) => $item['label'] && $item['value'])
                    ->values()
                    ->all()
            ),

            // Info adicional (uso, beneficios, modos de uso, etc.)
            'additional_info' => $this->whenLoaded(
                'additionalAttributes',
                fn () => $this->additionalAttributes
                    ->map(fn ($attr) => [
                        'label' => $attr->values['label'] ?? null,
                        'value' => $attr->values['value'] ?? null,
                    ])
                    ->filter(fn ($item) => $item['label'] && $item['value'])
                    ->values()
                    ->all()
            ),

            // Ficha nutricional — tabla con 3 columnas
            'nutritional_info' => $this->whenLoaded('nutritionalAttributes', function () {
                $serving_note = null;
                $rows = [];

                foreach ($this->nutritionalAttributes as $attr) {
                    if (isset($attr->values['serving_note'])) {
                        $serving_note = $attr->values['serving_note'];
                    } else {
                        $rows[] = [
                            'label' => $attr->values['label'] ?? null,
                            'value' => $attr->values['value'] ?? null,
                            'daily_value' => $attr->values['daily_value'] ?? null,
                        ];
                    }
                }

                return [
                    'serving_note' => $serving_note,
                    'rows' => array_filter($rows, fn ($r) => $r['label']),
                ];
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];

        // Campos por tipo de producto
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

        $data['is_wishlisted'] = $this->when(
            $request->user() !== null,
            fn () => $request->user()->wishlists()->where('product_id', (int) $this->id)->exists(),
        );

        return $data;
    }
}
