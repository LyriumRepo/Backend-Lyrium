<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canCancel = $this->resource->isPendingSeller();
        $canConfirm = $this->resource->canBeConfirmedBySeller();
        $canUpdate = $this->resource->canBeUpdatedBySeller();

        $firstItem = $this->whenLoaded('items', fn () => $this->items->first());
        $firstServiceItem = $this->whenLoaded('serviceItems', fn () => $this->serviceItems->first());
        $firstShipment = $this->whenLoaded('shipments', fn () => $this->shipments->first());

        $hasItems = $this->relationLoaded('items') && $this->items->isNotEmpty();
        $hasServiceItems = $this->relationLoaded('serviceItems') && $this->serviceItems->isNotEmpty();

        $orderType = match (true) {
            $hasItems && $hasServiceItems => 'mixed',
            $hasServiceItems => 'service',
            default => 'product',
        };

        $itemsSummary = '';
        if ($hasItems) {
            $firstName = $this->items->first()?->product_name ?? $this->items->first()?->product?->name ?? '';
            $count = $this->items->count();
            $itemsSummary = $count <= 1 ? $firstName : "{$firstName} + {$count} más";
        } elseif ($hasServiceItems) {
            $firstName = $this->serviceItems->first()?->service_name ?? '';
            $count = $this->serviceItems->count();
            $itemsSummary = $count <= 1 ? $firstName : "{$firstName} + {$count} más";
        }

        $totalQty = 0;
        if ($hasItems) {
            $totalQty += (int) $this->items->sum('quantity');
        }
        if ($hasServiceItems) {
            $totalQty += (int) $this->serviceItems->sum('quantity');
        }

        $storeName = null;
        if ($hasItems) {
            $storeName = $firstItem?->store?->store_name ?? $firstItem?->store?->trade_name;
        } elseif ($hasServiceItems) {
            $storeName = $firstServiceItem?->store_name_snapshot
                ?? $firstServiceItem?->store?->store_name
                ?? $firstServiceItem?->store?->trade_name;
        }

        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'orderNumber' => $this->order_number,
            'orderType' => $orderType,
            'itemsSummary' => $itemsSummary,
            'status' => $this->status,
            'globalStatus' => $this->computeGlobalStatus(),
            'statusLabel' => $this->getStatusLabel(),
            'paymentMethod' => $this->payment_method,
            'paymentStatus' => $this->payment_status,
            'paymentStatusLabel' => $this->payment_status_label,
            'totalQuantity' => $totalQty,
            'shippingType' => $this->shipping_type,
            'trackingNumber' => $firstShipment?->tracking_number,
            'carrier' => $firstShipment?->carrier,
            'carrierCode' => $firstShipment?->carrier_data['carrier_code'] ?? $firstShipment?->carrier,
            'carrierData' => $firstShipment?->carrier_data,
            'storeName' => $storeName,
            'shipping' => [
                'name' => $this->shipping_name,
                'email' => $this->shipping_email,
                'phone' => $this->shipping_phone,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'postalCode' => $this->shipping_postal_code,
                'notes' => $this->shipping_notes,
                'type' => $this->shipping_type,
            ],
            'subtotal' => (float) $this->subtotal,
            'shippingCost' => (float) $this->shipping_cost,
            'taxAmount' => (float) $this->tax_amount,
            'discountAmount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'couponCode' => $this->coupon_code,
            'notes' => $this->notes,
            'actions' => [
                'canCancel' => $canCancel,
                'canConfirm' => $canConfirm,
                'canUpdate' => $canUpdate,
            ],
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)->resolve($request)),
            'serviceItems' => $this->whenLoaded('serviceItems', fn () => OrderServiceItemResource::collection($this->serviceItems)->resolve($request)),
            'paidAt' => $this->when($this->payment_status === 'paid', function () {
                $culqi = $this->relationLoaded('latestCulqiTransaction') ? $this->latestCulqiTransaction : null;
                if ($culqi && $culqi->isPaid()) {
                    return $culqi->updated_at?->toIso8601String();
                }
                $izipay = $this->relationLoaded('latestIzipayTransaction') ? $this->latestIzipayTransaction : null;
                if ($izipay && $izipay->isPaid()) {
                    return $izipay->updated_at?->toIso8601String();
                }
                return $this->updated_at?->toIso8601String();
            }),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => (string) $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'documentType' => $this->user->document_type,
                'documentNumber' => $this->user->document_number,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
