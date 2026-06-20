<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TransactionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'productName' => $this->product_name,
            'unitPrice' => (float) $this->unit_price,
            'quantity' => (int) $this->quantity,
            'lineTotal' => (float) $this->line_total,
            'commissionRate' => (float) ($this->commission_rate ?? 0),
            'commissionAmount' => (float) ($this->commission_amount ?? 0),
            'productType' => $this->whenLoaded('product', fn () => $this->product?->type),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => (string) $this->store->id,
                'name' => $this->store->trade_name,
                'slug' => $this->store->slug,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => (string) $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ]),
        ];
    }
}
