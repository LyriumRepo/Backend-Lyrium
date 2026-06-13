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

    public function calculateItemCommission(OrderItem $item): void
    {
        $valorVenta = $item->unit_price / 1.18;
        $tier = $this->getTierForValue($valorVenta);
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
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $this->calculateItemCommission($item);
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

        $totalCommissionItems = $items->sum('commission_amount');

        $shippingValorVenta = $order->shipping_cost / 1.18;
        $store = $this->getOrderStore($order);
        $shippingRate = $store ? (float) $store->commission_rate * 100 : 15.0;
        $shippingCommission = round($shippingValorVenta * ($shippingRate / 100), 2);

        $totalCommission = $totalCommissionItems + $shippingCommission;
        $commissionIgv = round($totalCommission * 0.18, 2);

        return [
            'items' => $items,
            'shipping_valor_venta' => round($shippingValorVenta, 2),
            'shipping_commission_rate' => $shippingRate,
            'shipping_commission' => $shippingCommission,
            'total_commission' => $totalCommission,
            'commission_igv' => $commissionIgv,
            'commission_total' => $totalCommission + $commissionIgv,
        ];
    }

    private function getOrderStore(Order $order): ?Store
    {
        $order->loadMissing('items.store');
        $firstItem = $order->items->first();

        return $firstItem?->store;
    }
}
