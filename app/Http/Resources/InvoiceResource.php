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

        $storeCommissions = $this->store_commissions;

        if (! $storeCommissions && $this->relationLoaded('order') && $this->order) {
            $storeCommissions = $this->order->items->groupBy('store_id')->map(fn ($items) => [
                'storeId' => (string) $items->first()->store_id,
                'storeName' => $items->first()->store?->trade_name ?? $items->first()->store?->store_name ?? '—',
                'storeSlug' => $items->first()->store?->slug ?? '',
                'subtotal' => round($items->sum('line_total'), 2),
                'commissionRate' => (float) ($items->first()->commission_rate ?? 0),
                'commissionAmount' => round($items->sum('commission_amount'), 2),
                'commissionIgv' => round($items->sum('commission_amount') * 0.18 / 1.18, 2),
                'commissionTotal' => round($items->sum('commission_amount'), 2),
            ])->values()->toArray();
        }

        return [
            'id' => (string) $this->id,
            'orderId' => $this->order_id,
            'invoiceNumber' => $this->invoice_number,
            'documentType' => $this->document_type,
            'type' => $documentTypes[$this->document_type] ?? 'OTRO',
            'series' => $this->series,
            'number' => $this->number,
            'nit' => $this->nit,
            'businessName' => $this->business_name,
            'customerDocumentType' => $this->customer_document_type,
            'customerAddress' => $this->customer_address,
            'customerEmail' => $this->customer_email,
            'provider' => $this->provider,
            'providerInvoiceId' => $this->provider_invoice_id,
            'qrData' => $this->qr_data,
            'pdfUrl' => $this->pdf_url,
            'authorizationCode' => $this->authorization_code,
            'total' => (float) $this->total,
            'status' => $this->status,
            'items' => $this->items,
            'storeCommissions' => $storeCommissions,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => (string) $this->order->id,
                'orderNumber' => $this->order->order_number,
                'total' => (float) $this->order->total,
                'status' => $this->order->status,
                'items' => $this->order->items->map(fn ($item) => [
                    'productName' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unitPrice' => (float) $item->unit_price,
                    'lineTotal' => (float) $item->line_total,
                    'commissionAmount' => (float) ($item->commission_amount ?? 0),
                    'commissionRate' => (float) ($item->commission_rate ?? 0),
                    'storeName' => $item->store?->trade_name ?? $item->store?->store_name ?? null,
                    'storeSlug' => $item->store?->slug ?? null,
                ]),
                'stores' => $this->order->items->groupBy('store_id')->map(fn ($items) => [
                    'id' => (string) $items->first()->store_id,
                    'name' => $items->first()->store?->trade_name ?? $items->first()->store?->store_name ?? '—',
                    'slug' => $items->first()->store?->slug ?? '',
                ])->values(),
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
