<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => (int) $this->id,
            'product_id' => (int) $this->product_id,
            'product' => $product ? [
                'id' => (int) $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => (float) $product->price,
                'original_price' => $product->getRawOriginal('price') > $product->price
                    ? (float) $product->getRawOriginal('price')
                    : null,
                'image' => $product->getFirstMediaUrl('images') ?: $product->image ?: '',
                'stock' => (int) $product->stock,
                'store_name' => $product->store?->trade_name ?? '',
                'store_slug' => $product->store?->slug ?? '',
                'status' => $product->status,
                'sticker' => $product->sticker,
                'discount_percentage' => $product->discount_percentage,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
