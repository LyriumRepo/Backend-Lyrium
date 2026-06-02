<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'series' => $this->series ?? '',
            'number' => $this->number ?? '',
            'type' => $this->type ?? 'FACTURA',
            'customer_name' => $this->whenLoaded('order') && $this->order->relationLoaded('user') && $this->order->user
                ? $this->order->user->name
                : ($this->customer_name ?? $this->business_name ?? ''),
            'customer_ruc' => $this->whenLoaded('order') && $this->order->relationLoaded('user') && $this->order->user
                ? ($this->order->user->document_number ?? '')
                : ($this->customer_ruc ?? $this->nit ?? ''),
            'order_id' => (string) $this->order_id,
            'amount' => (float) $this->total,
            'emission_date' => $this->emission_date?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'sunat_status' => $this->sunat_status ?? 'DRAFT',
            'status' => $this->status,
            'pdf_url' => $this->pdf_url,
            'history' => $this->history ?? [],
            'store_id' => $this->store_id ? (string) $this->store_id : null,
            'invoice_number' => $this->invoice_number,
            'provider' => $this->provider,
            'provider_invoice_id' => $this->provider_invoice_id,
            'qr_data' => $this->qr_data,
            'authorization_code' => $this->authorization_code,
            'xml_url' => $this->xml_url,
            'cdr_url' => $this->cdr_url,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'total' => (float) $this->order->total,
                'status' => $this->order->status,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
