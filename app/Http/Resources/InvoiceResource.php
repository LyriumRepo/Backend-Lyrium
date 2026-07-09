<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            // Subtotal de productos del vendedor con IGV (sin envío) — para columna Monto en panel
            'storeAmount'          => round((float) $this->subtotal_sin_igv * (1 + 0.18), 2),

            // Total del pedido (lo que pagó el cliente por Izipay, incluye envío)
            'orderTotal'           => $this->whenLoaded('order', fn () => (float) $this->order->total, (float) $this->total),

            // Comisión: usa los campos del item si existen; si no, cae al commission_rate de la tienda
            'commissionRate'       => $this->resolveCommissionRate(),
            'commissionAmount'     => $this->resolveCommissionAmount(),

            // Tipo de pedido: Producto / Servicio / Producto y Servicio
            'orderType'            => $this->resolveOrderTypeLabel(),

            // Vendedor (para panel de admin)
            'sellerName'           => $this->whenLoaded('store', fn () => $this->store?->owner?->name ?? ''),

            // Estado SUNAT
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

            // Comisiones por tienda
            'storeCommissions'     => $storeCommissions,

            // Orden
            'orderId'              => (string) $this->order_id,
            'order'                => $this->whenLoaded('order', fn () => [
                'id'          => (string) $this->order->id,
                'orderNumber' => $this->order->order_number,
                'total'       => (float) $this->order->total,
                'status'      => $this->order->status,
                // Filtrar por store_id de la factura para no exponer productos/servicios
                // de otras tiendas en pedidos multi-vendor. Incluye tanto order_items
                // (productos) como order_service_items (servicios).
                'items'       => $this->combinedOrderItems()->map(fn ($item) => [
                    'productName'      => $item->product?->name ?? $item->product_name ?? $item->service_name ?? '',
                    'quantity'         => (int) $item->quantity,
                    'unitPrice'        => (float) $item->unit_price,
                    'lineTotal'        => (float) $item->line_total,
                    'commissionAmount' => (float) ($item->commission_amount ?? 0),
                    'commissionRate'   => (float) ($item->commission_rate ?? 0),
                    'storeName'        => $item->store?->store_name ?? $item->store?->nombre_comercial ?? null,
                    'storeSlug'        => $item->store?->slug ?? null,
                ]),
                'stores' => $this->combinedOrderItems()
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

    /**
     * Ítems (order_items + order_service_items) de la tienda de esta factura.
     */
    private function combinedOrderItems()
    {
        if (! $this->relationLoaded('order') || ! $this->order) {
            return collect();
        }

        $products = $this->order->relationLoaded('items')
            ? ($this->store_id ? $this->order->items->where('store_id', $this->store_id) : $this->order->items)
            : collect();

        $services = $this->order->relationLoaded('serviceItems')
            ? ($this->store_id ? $this->order->serviceItems->where('store_id', $this->store_id) : $this->order->serviceItems)
            : collect();

        return $products->concat($services);
    }

    private function resolveOrderTypeLabel(): ?string
    {
        $items = $this->combinedOrderItems();
        if ($items->isEmpty()) {
            return null;
        }

        $hasProducts = $items->contains(fn ($item) => $item instanceof \App\Models\OrderItem);
        $hasServices = $items->contains(fn ($item) => $item instanceof \App\Models\OrderServiceItem);

        return match (true) {
            $hasProducts && $hasServices => 'Producto y Servicio',
            $hasServices => 'Servicio',
            default => 'Producto',
        };
    }

    private function resolveCommissionRate(): ?float
    {
        // 1. Desde order_items / order_service_items (pedidos nuevos desde jun-2026)
        $items = $this->combinedOrderItems();
        if ($items->isNotEmpty()) {
            $rate = $items->first()?->commission_rate;
            if ($rate !== null) {
                return (float) $rate;
            }
        }

        // 2. Fallback: commission_rate de la tienda (plan de suscripción)
        if ($this->relationLoaded('store') && $this->store?->commission_rate !== null) {
            return (float) $this->store->commission_rate;
        }

        return null;
    }

    private function resolveCommissionAmount(): ?float
    {
        // 1. Desde order_items / order_service_items (pedidos nuevos desde jun-2026)
        $items = $this->combinedOrderItems();
        if ($items->isNotEmpty()) {
            $stored = (float) $items->sum('commission_amount');
            if ($stored > 0) {
                return $stored;
            }

            // Calcular desde line_total / 1.18 × (rate / 100) (pedidos antiguos sin el campo poblado)
            $rate = $this->resolveCommissionRate();
            if ($rate !== null) {
                $lineTotal = (float) $items->sum('line_total');
                if ($lineTotal > 0) {
                    $baseSinIgv = $lineTotal / (1 + 0.18);
                    return round($baseSinIgv * ($rate / 100), 2);
                }
            }
        }

        // 3. Último recurso: subtotal_sin_igv del invoice × (rate / 100)
        $rate = $this->resolveCommissionRate();
        if ($rate !== null) {
            return round((float) $this->subtotal_sin_igv * ($rate / 100), 2);
        }

        return null;
    }
}
