<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'price' => (float) $this->product->price,
                'original_price' => $this->product->regular_price ? (float) $this->product->regular_price : null,
                'image' => $this->product->getFirstMediaUrl('images') ?: '',
                'stock' => $this->product->stock,
                'store_name' => $this->product->store->name ?? '',
                'store_slug' => $this->product->store->slug ?? '',
                'status' => $this->product->status,
                'sticker' => $this->product->sticker,
                'discount_percentage' => $this->product->discount_percentage,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
