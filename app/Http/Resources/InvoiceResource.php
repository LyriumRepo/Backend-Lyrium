<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $documentTypes = [
            '1' => 'FACTURA',
            '2' => 'BOLETA',
            '3' => 'N.CREDITO',
            '4' => 'N.DEBITO',
        ];

        return [
            'id' => (string) $this->id,
            'orderId' => $this->order_id,
            'order_id' => (string) $this->order_id,
            'invoiceNumber' => $this->invoice_number,
            'invoice_number' => $this->invoice_number,
            'documentType' => $this->document_type,
            'type' => $this->type ?? $documentTypes[$this->document_type] ?? 'FACTURA',
            'series' => $this->series ?? '',
            'number' => $this->number ?? '',
            'nit' => $this->nit,
            'businessName' => $this->business_name,
            'customerDocumentType' => $this->customer_document_type,
            'customerAddress' => $this->customer_address,
            'customerEmail' => $this->customer_email,
            'customer_name' => $this->whenLoaded('order') && $this->order->relationLoaded('user') && $this->order->user
                ? $this->order->user->name
                : ($this->customer_name ?? $this->business_name ?? ''),
            'customer_ruc' => $this->whenLoaded('order') && $this->order->relationLoaded('user') && $this->order->user
                ? ($this->order->user->document_number ?? '')
                : ($this->customer_ruc ?? $this->nit ?? ''),
            'provider' => $this->provider,
            'providerInvoiceId' => $this->provider_invoice_id,
            'provider_invoice_id' => $this->provider_invoice_id,
            'qrData' => $this->qr_data,
            'qr_data' => $this->qr_data,
            'pdfUrl' => $this->pdf_url,
            'pdf_url' => $this->pdf_url,
            'xml_url' => $this->xml_url,
            'cdr_url' => $this->cdr_url,
            'authorizationCode' => $this->authorization_code,
            'authorization_code' => $this->authorization_code,
            'total' => (float) $this->total,
            'amount' => (float) $this->total,
            'status' => $this->status,
            'sunat_status' => $this->sunat_status ?? 'DRAFT',
            'emission_date' => $this->emission_date?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'history' => $this->history ?? [],
            'store_id' => $this->store_id ? (string) $this->store_id : null,
            'items' => $this->items,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'total' => (float) $this->order->total,
                'status' => $this->order->status,
                'items' => $this->order->items->map(fn ($item) => [
                    'productName' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unitPrice' => (float) $item->unit_price,
                    'lineTotal' => (float) $item->line_total,
                    'storeName' => $item->store?->trade_name ?? $item->store?->store_name ?? null,
                    'storeSlug' => $item->store?->slug ?? null,
                ]),
                'stores' => $this->order->items->groupBy('store_id')->map(fn ($items) => [
                    'id' => (string) $items->first()->store_id,
                    'name' => $items->first()->store?->trade_name ?? $items->first()->store?->store_name ?? '—',
                    'slug' => $items->first()->store?->slug ?? '',
                ])->values(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
