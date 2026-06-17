<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentConfirmationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'orderNumber' => $this->order_number,
            'status' => $this->status,
            'paymentMethod' => $this->payment_method,
            'paymentStatus' => $this->payment_status,
            'paymentStatusLabel' => $this->payment_status_label,
            'shipping' => [
                'name' => $this->shipping_name,
                'email' => $this->shipping_email,
                'phone' => $this->shipping_phone,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
            ],
            'subtotal' => (float) $this->subtotal,
            'shippingCost' => (float) $this->shipping_cost,
            'taxAmount' => (float) $this->tax_amount,
            'discountAmount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
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
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
