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
<<<<<<< HEAD
                $product  = $item->product;
                $price    = (float) ($product?->sale_price ?? $product?->price ?? 0);
                $quantity = (int) $item->quantity;

                return [
                    'id'        => $item->id,           // int, no string compuesta
                    'productId' => $item->product_id,   // camelCase
                    'quantity'  => $quantity,
                    'unitPrice' => round($price, 2),
                    'lineTotal' => round($price * $quantity, 2),
                    'product'   => [                    // objeto anidado
                        'id'            => $product?->id,
                        'name'          => $product?->name ?? '',
                        'slug'          => $product?->slug ?? '',
                        'price'         => round($price, 2),
                        'regular_price' => $product?->price
                                            ? round((float) $product->price, 2)
                                            : null,
                        'stock'         => (int) ($product?->stock ?? 0),
                        'image'         => $product?->getFirstMediaUrl('images')
                                            ?: $product?->image
                                            ?: null,
=======
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
>>>>>>> rama-calderon
                    ],
                ];
            })->all();
        }, []);

<<<<<<< HEAD
        $subtotal = round((float) collect($items)->sum('lineTotal'), 2);
        $shipping = $subtotal > 0 ? 10.00 : 0.0;

        return [
            'items'     => $items,
            'subtotal'  => $subtotal,
            'shipping'  => $shipping,
            'discount'  => 0.0,
            'total'     => round($subtotal + $shipping, 2),
            'itemCount' => count($items),   // camelCase para coincidir con TS
=======
        $subtotal = collect($items)->sum('lineTotal');

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'itemCount' => (int) $this->item_count,
>>>>>>> rama-calderon
        ];
    
    }
}   