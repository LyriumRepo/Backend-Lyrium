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
            $storeId = (string) $first->store_id;
            $storeName = $first->store?->trade_name ?? $first->store?->store_name ?? '—';

            $commTotal = $storeItems->sum('commission_amount');
            $commIgv = round($commTotal * 0.18 / 1.18, 2);
            $commBase = round($commTotal - $commIgv, 2);

            return [
                'id' => $storeId,
                'name' => $storeName,
                'slug' => $first->store?->slug ?? '',
                'commissionAmount' => $commBase,
                'commissionIgv' => $commIgv,
                'commissionTotal' => $commTotal,
            ];
        })->values();

        $hasProduct = $items->contains(fn ($i) => in_array($i->product?->type, ['physical', 'digital'], true));
        $hasService = $items->contains(fn ($i) => $i->product?->type === 'service');
        $tipo = $hasProduct && $hasService ? 'ambos' : ($hasService ? 'servicio' : 'producto');

        $commissionTotal = $items->sum('commission_amount');
        $commissionIgv = round($commissionTotal * 0.18 / 1.18, 2);
        $commissionAmount = round($commissionTotal - $commissionIgv, 2);

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
            'tipo' => $tipo,
            'commissionAmount' => $commissionAmount,
            'commissionIgv' => $commissionIgv,
            'commissionTotal' => $commissionTotal,
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
