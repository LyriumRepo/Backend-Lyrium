<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', function () {
            return $this->items->map(function ($item) {
                $product = $item->product;
                $price = (float) ($product?->sale_price ?? $product?->price ?? 0);
                $quantity = (int) $item->quantity;

                return [
                    'id' => $item->id,
                    'productId' => $item->product_id,
                    'quantity' => $quantity,
                    'unitPrice' => round($price, 2),
                    'lineTotal' => round($price * $quantity, 2),
                    'product' => [
                        'id' => $product?->id,
                        'name' => $product?->name ?? '',
                        'slug' => $product?->slug ?? '',
                        'price' => round($price, 2),
                        'regular_price' => $product?->regular_price
                                            ? round((float) $product->regular_price, 2)
                                            : ($product?->price ? round((float) $product->price, 2) : null),
                        'stock' => (int) ($product?->stock ?? 0),
                        'image' => $product?->getFirstMediaUrl('images')
                                            ?? $product?->image
                                            ?? null,
                    ],
                ];
            })->all();
        }, []);

        $subtotal = round((float) collect($items)->sum('lineTotal'), 2);

        $shipping = (float) ($this->shipping ?? 0);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => 0.0,
            'total' => round($subtotal + $shipping, 2),
            'itemCount' => count($items),
            'meta' => [
                'item_count_raw' => (int) $this->item_count,
            ],
        ];

    }
}
