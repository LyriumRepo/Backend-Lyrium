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
            'id'                   => (string) $this->id,
            'series'               => $this->series ?? '',
            'number'               => $this->number ?? '',
            'type'                 => $this->type ?? 'FACTURA',
            'invoiceNumber'        => $this->invoice_number,
            'documentType'         => $this->document_type,
            'provider'             => $this->provider,

            // Cliente
            'businessName'         => $this->whenLoaded('order') && $this->order->relationLoaded('user') && $this->order->user
                ? $this->order->user->name
                : ($this->customer_name ?? $this->business_name ?? ''),
            'nit'                  => $this->whenLoaded('order') && $this->order->relationLoaded('user') && $this->order->user
                ? ($this->order->user->document_number ?? '')
                : ($this->customer_ruc ?? $this->nit ?? ''),
            'customerDocumentType' => $this->customer_document_type,
            'customerAddress'      => $this->customer_address,
            'customerEmail'        => $this->customer_email,

            // Tienda
            'storeId'              => $this->store_id ? (string) $this->store_id : null,
            'storeName'            => $this->whenLoaded('store')
                ? ($this->store->store_name ?? $this->store->nombre_comercial ?? '')
                : '',
            'storeRuc'             => $this->whenLoaded('store') ? ($this->store->ruc ?? '') : '',

            // Montos
            'total'                => (float) $this->total,
            'subtotalSinIgv'       => (float) $this->subtotal_sin_igv,
            'igvAmount'            => (float) $this->igv_amount,

            // Estado
            'status'               => $this->sunat_status ?? 'DRAFT',

            // Documentos
            'pdfUrl'               => $this->pdf_url,
            'xmlUrl'               => $this->xml_url,
            'cdrUrl'               => $this->cdr_url,
            'qrData'               => $this->qr_data,
            'authorizationCode'    => $this->authorization_code,
            'providerInvoiceId'    => $this->provider_invoice_id,

            // Ítems (JSON raw de Nubefact)
            'items'                => $this->items,
            'history'              => $this->history ?? [],

            // Orden
            'orderId'              => (string) $this->order_id,
            'order'                => $this->whenLoaded('order', fn () => [
                'id'          => (string) $this->order->id,
                'orderNumber' => $this->order->order_number,
                'total'       => (float) $this->order->total,
                'status'      => $this->order->status,
                'items'       => $this->order->items->map(fn ($item) => [
                    'productName' => $item->product?->name ?? $item->product_name ?? '',
                    'quantity'    => (int) $item->quantity,
                    'unitPrice'   => (float) $item->unit_price,
                    'lineTotal'   => (float) $item->line_total,
                    'storeName'   => $item->store?->store_name ?? $item->store?->nombre_comercial ?? null,
                    'storeSlug'   => $item->store?->slug ?? null,
                ]),
                'stores' => $this->order->items
                    ->pluck('store')
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->map(fn ($s) => [
                        'id'   => (string) $s->id,
                        'name' => $s->store_name ?? $s->nombre_comercial ?? '',
                        'slug' => $s->slug ?? '',
                    ]),
            ]),

            'createdAt'  => $this->emission_date?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'updatedAt'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
