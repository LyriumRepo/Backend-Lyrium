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
                $price = (float) ($item->product?->sale_price ?? $item->product?->price ?? 0);
                $lineTotal = $price * $item->quantity;

                return [
                    'id' => (int) $item->product_id,
                    'productId' => (int) $item->product_id,
                    'quantity' => (int) $item->quantity,
                    'unitPrice' => round($price, 2),
                    'lineTotal' => round($lineTotal, 2),
                    'product' => [
                        'id' => (int) $item->product_id,
                        'name' => $item->product?->name ?? '',
                        'slug' => $item->product?->slug ?? '',
                        'image' => $item->product?->getFirstMediaUrl('images')
                            ?? $item->product?->image
                            ?? null,
                        'price' => round($price, 2),
                        'regular_price' => (float) ($item->product?->regular_price ?? $item->product?->price ?? 0),
                        'stock' => (int) ($item->product?->stock ?? 0),
                    ],
                ];
            })->all();
        }, []);

        $subtotal = collect($items)->sum('lineTotal');

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'itemCount' => (int) $this->item_count,
        ];
    }
}
