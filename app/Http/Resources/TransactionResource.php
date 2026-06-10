<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $izipayTxn = $this->resource->relationLoaded('latestIzipayTransaction')
            ? $this->resource->latestIzipayTransaction
            : null;

        $items = $this->resource->relationLoaded('items')
            ? $this->resource->items
            : collect();

        $stores = $items->groupBy('store_id')->map(function ($storeItems) {
            $first = $storeItems->first();

            return [
                'id' => (string) $first->store_id,
                'name' => $first->store?->trade_name ?? $first->store?->store_name ?? '—',
                'slug' => $first->store?->slug ?? '',
            ];
        })->values();

        return [
            'id' => (string) $this->id,
            'orderNumber' => $this->order_number,
            'createdAt' => $this->created_at?->toIso8601String(),
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => (string) $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'stores' => $stores,
            'items' => TransactionItemResource::collection($items),
            'itemCount' => $items->sum('quantity'),
            'subtotal' => (float) $this->subtotal,
            'shippingCost' => (float) $this->shipping_cost,
            'discountAmount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'paymentMethod' => $izipayTxn?->payment_method_type ?? $this->payment_method,
            'paymentStatus' => $this->payment_status,
            'cardBrand' => $izipayTxn?->card_brand,
            'cardLast4' => $izipayTxn?->card_last4,
            'transactionStatus' => $izipayTxn?->transaction_status,
            'transactionUuid' => $izipayTxn?->transaction_uuid,
            'izipayOrderId' => $izipayTxn?->izipay_order_id,
            'mode' => $izipayTxn?->mode,
        ];
    }
}
