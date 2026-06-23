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

                $product  = $item->product;
                $store    = $product?->store;
                $price    = (float) ($product?->sale_price ?? $product?->price ?? 0);
                $quantity = (int) $item->quantity;
                $dims = $this->parseDimensions($product?->dimensions);
                $origen = [
                    'departamento' => strtoupper($store?->department ?? 'LA LIBERTAD'),
                    'provincia'    => strtoupper($store?->province   ?? 'TRUJILLO'),
                    'distrito'     => strtoupper($store?->district   ?? 'TRUJILLO'),
                ];

                return [
                    'id'        => $item->id,
                    'productId' => $item->product_id,
                    'quantity'  => $quantity,
                    'unitPrice' => round($price, 2),
                    'lineTotal' => round($price * $quantity, 2),
                    'name'  => $product?->name ?? '',
                    'product' => [
                        'id'    => $product?->id,
                        'name'  => $product?->name ?? '',
                        'slug'  => $product?->slug ?? '',
                        'price' => round($price, 2),
                        'regular_price' => $product?->regular_price
                            ? round((float) $product->regular_price, 2)
                            : ($product?->price ? round((float) $product->price, 2) : null),

                        'stock' => (int) ($product?->stock ?? 0),
                        'image' => $product?->getFirstMediaUrl('images')
                            ?? $product?->image
                            ?? null,
                    ],

                    'peso'  => (float) ($product?->weight ?? 0.5),
                    'largo' => $dims['largo'],
                    'ancho' => $dims['ancho'],
                    'alto'  => $dims['alto'],

                    'store_id'   => $store?->id   ?? 0,
                    'store_name' => $store?->store_name ?? 'Tienda',
                    'store_slug' => $store?->slug ?? null,

                    'origen' => $origen,
                ];
            })->all();
        }, []);

        $subtotal = round((float) collect($items)->sum('lineTotal'), 2);
        $shipping = (float) ($this->shipping ?? 0);

        return [
            'items'     => $items,
            'subtotal'  => $subtotal,
            'shipping'  => $shipping,
            'discount'  => 0.0,
            'total'     => round($subtotal + $shipping, 2),
            'itemCount' => count($items),
            'meta' => [
                'item_count_raw' => (int) ($this->item_count ?? 0),
            ],
        ];
    }

    private function parseDimensions(?string $raw): array
    {
        $fallback = ['largo' => 30.0, 'ancho' => 20.0, 'alto' => 15.0];

        if (!$raw) return $fallback;

        $json = json_decode($raw, true);
        if (is_array($json)) {
            return [
                'largo' => (float) ($json['largo'] ?? $json['length'] ?? 30),
                'ancho' => (float) ($json['ancho'] ?? $json['width']  ?? 20),
                'alto'  => (float) ($json['alto']  ?? $json['height'] ?? 15),
            ];
        }

        $parts = preg_split('/[xX×*]/u', trim($raw));
        if (count($parts) >= 3) {
            return [
                'largo' => (float) ($parts[0] ?? 30),
                'ancho' => (float) ($parts[1] ?? 20),
                'alto'  => (float) ($parts[2] ?? 15),
            ];
        }

        return $fallback;
    }
}
