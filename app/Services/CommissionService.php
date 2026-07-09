<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CommissionTier;
use App\Models\Order;
use App\Models\OrderItem;
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

    // $orderSubtotal: total del pedido CON IGV (usado para determinar tramo, no precio individual)
    public function calculateItemCommission(OrderItem $item, float $orderSubtotal): void
    {
        $valorVentaOrden = $orderSubtotal / 1.18;
        $tier = $this->getTierForValue($valorVentaOrden);
        $rate = $tier->rate;

        $itemLineVenta = $item->line_total / 1.18;
        // Comisión Total por ítem = (valor_venta_neto × tasa); incluye IGV de la comisión
        $commissionAmount = round($itemLineVenta * ($rate / 100), 2);

        $item->updateQuietly([
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
        ]);
    }

    public function calculateForOrder(Order $order): void
    {
        $order->loadMissing('items');
        $orderSubtotal = (float) $order->subtotal;

        foreach ($order->items as $item) {
            $this->calculateItemCommission($item, $orderSubtotal);
        }
    }

    public function getCommissionSummary(Order $order): array
    {
        $order->loadMissing('items');

        $items = $order->items->map(fn (OrderItem $item) => [
            'product_name' => $item->product_name,
            'line_total' => (float) $item->line_total,
            'valor_venta' => round($item->line_total / 1.18, 2),
            'commission_rate' => (float) $item->commission_rate,
            'commission_amount' => (float) $item->commission_amount,
        ]);

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
