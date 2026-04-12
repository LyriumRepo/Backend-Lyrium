<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'amount' => $this->amount,
            'commission_rate' => $this->commission_rate,
            'commission_amount' => $this->commission_amount,
            'net_amount' => $this->net_amount,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'store' => $this->whenLoaded('store', fn () => new StoreResource($this->store)),
            'order' => $this->whenLoaded('order', fn () => new OrderResource($this->order)),
        ];
    }
}
