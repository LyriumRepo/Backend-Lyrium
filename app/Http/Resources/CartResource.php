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
                $price = $item->product?->sale_price ?? $item->product?->price ?? 0;
                $lineTotal = $price * $item->quantity;
                $timestamp = $item->created_at?->timestamp ?? now()->timestamp;

                return [
                    'id' => "{$item->product_id}-{$timestamp}",
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name ?? '',
                    'image' => $item->product?->getFirstMediaUrl('images')
                        ?? $item->product?->image
                        ?? null,
                    'price' => round($price, 2),
                    'quantity' => (int) $item->quantity,
                    'total' => round($lineTotal, 2),
                ];
            })->all();
        }, []);

        $subtotal = collect($items)->sum('total');
        $discount = 0.0;
        $shipping = $subtotal > 0 ? 10.00 : 0.0;
        $total = round($subtotal + $discount + $shipping, 2);

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'shipping' => round($shipping, 2),
            'total' => $total,
            'item_count' => (int) $this->item_count,
        ];
    }
}
