<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isSeller = $user && $user->hasAnyRole(['seller', 'administrator']);

        $canConfirm = false;
        $canCancel = false;
        $isOwner = false;

        if ($isSeller) {
            // Usar storeIds pre-computado del controller (evita N+1 de 2 queries por item)
            $storeIds = $request->attributes->get('seller_store_ids', []);
            if (empty($storeIds)) {
                $storeIds = $user->ownedStores()->pluck('id')
                    ->concat($user->stores()->pluck('stores.id'))
                    ->unique()
                    ->toArray();
            }
            $isOwner = in_array($this->store_id, $storeIds);

            $canConfirm = $isOwner && $this->status === \App\Models\OrderItem::STATUS_PENDING_SELLER;
            $canCancel = $isOwner && in_array($this->status, [
                \App\Models\OrderItem::STATUS_PENDING_SELLER,
                \App\Models\OrderItem::STATUS_CONFIRMED,
            ]);
        } elseif ($user && $user->id === $this->order->user_id) {
            $canCancel = $this->status === \App\Models\OrderItem::STATUS_PENDING_SELLER;
        }

        return [
            'id' => (string) $this->id,
            'sellerId' => $this->store_id,
            'isOwn' => $isOwner,
            'productId' => $this->product_id,
            'productName' => $this->product_name,
            'unitPrice' => (float) $this->unit_price,
            'quantity' => (int) $this->quantity,
            'lineTotal' => (float) $this->line_total,
            'shippingCost' => (float) (
                $this->order?->store_shipping
                ? collect($this->order->store_shipping)->firstWhere('store_id', $this->store_id)['shipping_cost'] ?? 0
                : 0
            ),
            'commissionRate' => (float) $this->commission_rate,
            'commissionAmount' => (float) $this->commission_amount,
            'status' => $this->status,
            'statusLabel' => $this->getStatusLabel(),
            'actions' => [
                'canConfirm' => $canConfirm,
                'canCancel' => $canCancel,
            ],
            'product' => $this->whenLoaded('product', fn () => [
                'id' => (string) $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'image' => $this->product->getFirstMediaUrl('images') ?? $this->product->image ?? '',
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => (string) $this->store->id,
                'name' => $this->store->trade_name,
                'slug' => $this->store->slug,
            ]),
        ];
    }
}
