<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FinanceAnalyticsController extends Controller
{
    private function getStore($user): ?\App\Models\Store
    {
        return $user->stores()->first()
            ?? $user->ownedStores()->first();
    }

    public function analytics(Request $request): JsonResponse
    {
        $store = $this->getStore($request->user());
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No tienes una tienda'], 403);
        }

        $tiempoRespuesta = $this->computeTiempoRespuesta($store->id);
        $csat = $this->computeCsat($store->id);
        $stockRotation = $this->computeStockRotation($store->id);
        $cuotaMercado = $this->computeCuotaMercado($store->id);

        return response()->json([
            'success' => true,
            'data' => [
                'tiempoRespuesta' => $tiempoRespuesta,
                'csat' => $csat,
                'stockRotation' => $stockRotation,
                'cuotaMercado' => $cuotaMercado,
            ],
        ]);
    }

    private function computeTiempoRespuesta(int $storeId): array
    {
        $tickets = Ticket::where('store_id', $storeId)
            ->whereHas('messages')
            ->with(['messages' => fn($q) => $q->orderBy('created_at')])
            ->get();

        if ($tickets->isEmpty()) {
            return [0, 0, 0, 0];
        }

        $weeklyData = [];

        foreach ($tickets as $ticket) {
            $prev = $ticket->created_at;

            foreach ($ticket->messages as $msg) {
                $minutes = $prev->diffInMinutes($msg->created_at);
                $week = $msg->created_at->startOfWeek()->toDateString();

                if (!isset($weeklyData[$week])) {
                    $weeklyData[$week] = ['sum' => 0.0, 'count' => 0];
                }

                $weeklyData[$week]['sum'] += $minutes;
                $weeklyData[$week]['count']++;

                $prev = $msg->created_at;
            }
        }

        $result = [];

        for ($i = 3; $i >= 0; $i--) {
            $week = now()->subWeeks($i)->startOfWeek()->toDateString();

            if (isset($weeklyData[$week]) && $weeklyData[$week]['count'] > 0) {
                $result[] = round($weeklyData[$week]['sum'] / $weeklyData[$week]['count'], 2);
            } else {
                $result[] = 0;
            }
        }

        return $result;
    }

    private function computeCsat(int $storeId): float
    {
        $avg = Ticket::where('store_id', $storeId)
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');

        return round($avg ?? 0, 2);
    }

    private function computeStockRotation(int $storeId): array
    {
        $totalStock = (float) Product::where('store_id', $storeId)->sum('stock');

        if ($totalStock <= 0) {
            return [0, 0, 0, 0];
        }

        $quarters = [];

        for ($i = 3; $i >= 0; $i--) {
            $start = now()->subQuarters($i)->startOfQuarter();
            $end = now()->subQuarters($i)->endOfQuarter();

            $unitsSold = (float) OrderItem::where('store_id', $storeId)
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');

            $quarters[] = round($unitsSold / $totalStock, 2);
        }

        return $quarters;
    }

    private function computeCuotaMercado(int $storeId): array
    {
        $storeSales = (float) OrderItem::where('store_id', $storeId)->sum('line_total');
        $marketplaceSales = (float) OrderItem::sum('line_total');

        $percentage = $marketplaceSales > 0
            ? round(($storeSales / $marketplaceSales) * 100, 2)
            : 0;

        $storeCategorySales = OrderItem::where('order_items.store_id', $storeId)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('category_product', 'products.id', '=', 'category_product.product_id')
            ->join('categories', 'category_product.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name')
            ->selectRaw('SUM(order_items.line_total) as total')
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $marketplaceCategorySales = OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('category_product', 'products.id', '=', 'category_product.product_id')
            ->join('categories', 'category_product.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name')
            ->selectRaw('SUM(order_items.line_total) as total')
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $categoryBreakdown = [];

        foreach ($marketplaceCategorySales as $mc) {
            $storeCat = $storeCategorySales->firstWhere('id', $mc->id);
            $storeTotal = $storeCat ? (float) $storeCat->total : 0;
            $marketplaceTotal = (float) $mc->total;
            $share = $marketplaceTotal > 0
                ? round(($storeTotal / $marketplaceTotal) * 100, 2)
                : 0;

            $categoryBreakdown[] = [
                'category_id' => $mc->id,
                'category_name' => $mc->name,
                'store_sales' => $storeTotal,
                'marketplace_sales' => $marketplaceTotal,
                'share_percentage' => $share,
            ];
        }

        return [
            'overall_percentage' => $percentage,
            'store_total_sales' => $storeSales,
            'marketplace_total_sales' => $marketplaceSales,
            'by_category' => $categoryBreakdown,
        ];
    }
}
