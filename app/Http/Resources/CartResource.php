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

                $store = $product?->store;

                // Parsear dimensiones "LARGOxANCHOxALTO" si existen
                $largo = 30.0; $ancho = 20.0; $alto = 15.0;
                if ($product?->dimensions) {
                    $parts = preg_split('/[xX×\s]+/', trim($product->dimensions));
                    if (count($parts) >= 3) {
                        $largo = max(0.5, (float) $parts[0]);
                        $ancho = max(0.5, (float) $parts[1]);
                        $alto  = max(0.5, (float) $parts[2]);
                    }
                }

                return [
                    'id'        => $item->id,
                    'productId' => $item->product_id,
                    'quantity'  => $quantity,
                    'unitPrice' => round($price, 2),
                    'lineTotal' => round($price * $quantity, 2),
                    'product'   => [
                        'id'            => $product?->id,
                        'name'          => $product?->name ?? '',
                        'slug'          => $product?->slug ?? '',
                        'price'         => round($price, 2),
                        'regular_price' => $product?->regular_price
                                            ? round((float) $product->regular_price, 2)
                                            : ($product?->price ? round((float) $product->price, 2) : null),
                        'stock' => (int) ($product?->stock ?? 0),
                        'image' => $product?->getFirstMediaUrl('images')
                                    ?? $product?->image
                                    ?? null,
                    ],
                    // Campos de logística
                    'store_id'   => $product?->store_id,
                    'store_name' => $store?->trade_name ?? $store?->name ?? '',
                    'store_slug' => $store?->slug ?? null,
                    'peso'  => max(0.001, (float) ($product?->weight ?? 0.5)),
                    'largo' => $largo,
                    'ancho' => $ancho,
                    'alto'  => $alto,
                    'origen' => $store && $store->department && $store->province && $store->district
                        ? [
                            'departamento' => strtoupper($store->department),
                            'provincia'    => strtoupper($store->province),
                            'distrito'     => strtoupper($store->district),
                        ]
                        : null,
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
