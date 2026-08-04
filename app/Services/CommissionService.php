<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CommissionTier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderServiceItem;
use App\Models\Store;

final class CommissionService
{
    public function getTierForValue(float $valorVenta): CommissionTier
    {
        $tier = CommissionTier::orderBy('sort_order')
            ->where('min_amount', '<=', $valorVenta)
            ->where(function ($q) use ($valorVenta) {
                $q->where('max_amount', '>=', $valorVenta)
                    ->orWhereNull('max_amount');
            })
            ->first();

        return $tier ?? CommissionTier::orderBy('sort_order')->first();
    }

    // $storeSubtotal: subtotal de la tienda CON IGV (sin envío); los tramos están en valores con IGV
    public function calculateItemCommission(OrderItem $item, float $storeSubtotal): void
    {
        // Los tramos (0-400, 401-800…) se evalúan sobre la venta de cada tienda individualmente
        $tier = $this->getTierForValue($storeSubtotal);
        $rate = $tier->rate;

        $itemLineVenta = $item->line_total / 1.18;
        // Comisión Total por ítem = (valor_venta_neto × tasa); incluye IGV de la comisión
        $commissionAmount = round($itemLineVenta * ($rate / 100), 2);

        $item->updateQuietly([
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
        ]);
    }

    // Misma lógica que calculateItemCommission pero para ítems de servicio
    public function calculateServiceItemCommission(OrderServiceItem $item, float $storeSubtotal): void
    {
        $tier = $this->getTierForValue($storeSubtotal);
        $rate = $tier->rate;

        $itemLineVenta = $item->line_total / 1.18;
        $commissionAmount = round($itemLineVenta * ($rate / 100), 2);

        $item->updateQuietly([
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
        ]);
    }

    public function calculateForOrder(Order $order): void
    {
        $order->loadMissing(['items', 'serviceItems']);

        // Agrupar por tienda (productos + servicios) para que cada una evalúe su propio
        // tramo de comisión sobre su venta total. Usar el subtotal global de la orden
        // mezclaría ventas de distintos vendedores y podría llevar a un tramo distinto
        // al que le corresponde a cada uno.
        $itemsByStore = $order->items->groupBy('store_id');
        $serviceItemsByStore = $order->serviceItems->groupBy('store_id');
        $storeIds = $itemsByStore->keys()->merge($serviceItemsByStore->keys())->unique();

        foreach ($storeIds as $storeId) {
            $storeItems = $itemsByStore->get($storeId, collect());
            $storeServiceItems = $serviceItemsByStore->get($storeId, collect());
            $storeSubtotal = (float) $storeItems->sum('line_total') + (float) $storeServiceItems->sum('line_total');

            foreach ($storeItems as $item) {
                $this->calculateItemCommission($item, $storeSubtotal);
            }
            foreach ($storeServiceItems as $item) {
                $this->calculateServiceItemCommission($item, $storeSubtotal);
            }
        }
    }

    public function getCommissionSummary(Order $order, ?int $storeId = null): array
    {
        $order->loadMissing(['items', 'serviceItems']);

        $productSource = $storeId
            ? $order->items->where('store_id', $storeId)
            : $order->items;
        // Un pedido puede mezclar productos y servicios de la misma tienda — faltaba
        // sumar los ítems de servicio aquí, así que la comisión de servicios (ya
        // calculada y guardada por calculateForOrder()) se omitía del resumen/PDF.
        $serviceSource = $storeId
            ? $order->serviceItems->where('store_id', $storeId)
            : $order->serviceItems;

        $productItems = $productSource->map(fn (OrderItem $item) => [
            'product_name' => $item->product_name,
            'line_total' => (float) $item->line_total,
            'valor_venta' => round($item->line_total / 1.18, 2),
            'commission_rate' => (float) $item->commission_rate,
            'commission_amount' => (float) $item->commission_amount,
        ]);

        $serviceItems = $serviceSource->map(fn (OrderServiceItem $item) => [
            'product_name' => $item->service_name,
            'line_total' => (float) $item->line_total,
            'valor_venta' => round($item->line_total / 1.18, 2),
            'commission_rate' => (float) $item->commission_rate,
            'commission_amount' => (float) $item->commission_amount,
        ]);

        $items = $productItems->concat($serviceItems);

        // commission_amount por ítem = "Comisión Total" (incluye IGV de la comisión).
        // Sin envío (fórmula Lyrium aplica solo sobre venta de productos).
        $commissionConIgv = $items->sum('commission_amount');

        // Descomposición SUNAT: extraer IGV de la comisión (no agregar encima)
        $commissionBase = round($commissionConIgv / 1.18, 2);
        $commissionIgv  = round($commissionBase * 0.18, 2);

        return [
            'items'            => $items,
            'commission_base'  => $commissionBase,   // base imponible (sin IGV)
            'commission_igv'   => $commissionIgv,    // IGV extraído de la comisión
            'commission_total' => $commissionConIgv, // total a cobrar (base + IGV)
        ];
    }
}
