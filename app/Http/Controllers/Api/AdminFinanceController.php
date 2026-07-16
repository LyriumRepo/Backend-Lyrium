<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SellerPayment;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminFinanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $ingresosBrutos = $this->getIngresosBrutos($startDate, $endDate);
        $ingresosNetos = $this->getIngresosNetos($startDate, $endDate);
        $ingresosReales = $this->getIngresosReales($startDate, $endDate);
        $ventasTotales = $this->getVentasTotales($startDate, $endDate);
        $ticketPromedio = $this->getTicketPromedio($startDate, $endDate);
        $leadTime = $this->getLeadTime($startDate, $endDate);
        $defectuosos = $this->getDefectuosos();
        $tiempoRespuesta = $this->getTiempoRespuesta($startDate, $endDate);
        $stockRotacion = $this->getStockRotacion();
        $roi = $this->getROI($ingresosBrutos);
        $ltv = $this->getLTV($startDate, $endDate);
        $cuotaMercado = $this->getCuotaMercado($startDate, $endDate);
        $proxPago = $this->getProximoPago();
        $topBuyers = $this->getTopBuyers($startDate, $endDate);
        $heatmap = $this->getHeatmap($startDate, $endDate);
        $categories = $this->getCategories($startDate, $endDate);
        $csat = $this->getCSAT($startDate, $endDate);
        $desgloseFinanciero = $this->getDesgloseFinanciero($startDate, $endDate);
        $comprobantesRecientes = $this->getComprobantesRecientes();

        return response()->json([
            'success' => true,
            'data' => [
                'ingresosBrutos' => $ingresosBrutos,
                'ingresosNetos' => $ingresosNetos,
                'ingresosReales' => $ingresosReales,
                'ventasTotales' => $ventasTotales,
                'ticketPromedio' => $ticketPromedio,
                'chartProxPago' => $proxPago['chart'],
                'roi' => $roi,
                'cuotaMercado' => $cuotaMercado,
                'ltv' => $ltv,
                'categories' => $categories,
                'leadTime' => $leadTime,
                'defectuosos' => $defectuosos,
                'tiempoRespuesta' => $tiempoRespuesta,
                'stockRotacion' => $stockRotacion,
                'csat' => $csat,
                'heatmap' => $heatmap,
                'topBuyers' => $topBuyers,
                'desgloseFinanciero' => $desgloseFinanciero,
                'comprobantesRecientes' => $comprobantesRecientes,
            ],
        ]);
    }

    private function getIngresosBrutos(string $start, string $end): array
    {
        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periodo"), DB::raw('SUM(total) as total'))
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $labels = $rows->pluck('periodo')->map(fn ($p) => ucfirst(\Carbon\Carbon::parse($p.'-01')->translatedFormat('M')))->toArray();
        $data = $rows->pluck('total')->map(fn ($v) => (float) $v)->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => [], 'trend' => '0%'];
        }

        $total = array_sum($data);
        $prevTotal = 0;

        return [
            'labels' => $labels,
            'data' => $data,
            'trend' => $total > 0 ? '+'.number_format(($total - $prevTotal) / max($prevTotal, 1) * 100, 1).'%' : '0%',
        ];
    }

    private function getIngresosNetos(string $start, string $end): array
    {
        $avgCommission = (float) DB::table('stores')->avg('commission_rate') / 100;

        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periodo"), DB::raw('SUM(total) as total'))
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $labels = $rows->pluck('periodo')->map(fn ($p) => ucfirst(\Carbon\Carbon::parse($p.'-01')->translatedFormat('M')))->toArray();
        $data = $rows->pluck('total')->map(fn ($v) => round((float) $v * (1 - $avgCommission), 2))->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => [], 'trend' => '0%'];
        }

        return ['labels' => $labels, 'data' => $data, 'trend' => '+0%'];
    }

    private function getIngresosReales(string $start, string $end): array
    {
        $avgCommission = (float) DB::table('stores')->avg('commission_rate') / 100;

        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periodo"),
                DB::raw('SUM(total) as total'),
                DB::raw('SUM(shipping_cost) as shipping')
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $labels = $rows->pluck('periodo')->map(fn ($p) => ucfirst(\Carbon\Carbon::parse($p.'-01')->translatedFormat('M')))->toArray();
        $data = $rows->map(fn ($r) => round((float) $r->total * (1 - $avgCommission) - (float) $r->shipping, 2))->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => [], 'trend' => '0%'];
        }

        return ['labels' => $labels, 'data' => $data, 'trend' => '+0%'];
    }

    private function getVentasTotales(string $start, string $end): array
    {
        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periodo"), DB::raw('COUNT(*) as count'))
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $labels = $rows->pluck('periodo')->map(fn ($p) => ucfirst(\Carbon\Carbon::parse($p.'-01')->translatedFormat('M')))->toArray();
        $data = $rows->pluck('count')->map(fn ($v) => (int) $v)->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => [], 'trend' => '0%'];
        }

        return ['labels' => $labels, 'data' => $data, 'trend' => '+0%'];
    }

    private function getTicketPromedio(string $start, string $end): array
    {
        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as dia"), DB::raw('AVG(total) as avg_total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $labels = $rows->pluck('dia')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray();
        $data = $rows->pluck('avg_total')->map(fn ($v) => round((float) $v, 2))->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => [], 'trend' => '0%'];
        }

        return ['labels' => $labels, 'data' => $data, 'trend' => '+0%'];
    }

    private function getLeadTime(string $start, string $end): array
    {
        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periodo"),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours'))
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $labels = $rows->pluck('periodo')->map(fn ($p) => ucfirst(\Carbon\Carbon::parse($p.'-01')->translatedFormat('M')))->toArray();
        $data = $rows->pluck('avg_hours')->map(fn ($v) => round((float) $v, 1))->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getDefectuosos(): array
    {
        $disputesCount = DB::table('disputes')->count();
        $totalProducts = DB::table('order_items')->count();
        $rate = $totalProducts > 0 ? round($disputesCount / $totalProducts * 100, 2) : 0;

        return [
            'labels' => ['Sin Defectos', 'Defectuosos'],
            'data' => [$rate > 0 ? round(100 - $rate, 2) : 100, $rate],
        ];
    }

    private function getTiempoRespuesta(string $start, string $end): array
    {
        $avgMinutes = TicketMessage::whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->where('type', 'normal')
            ->whereHas('ticket', fn ($q) => $q->where('status', '!=', 'open'))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(updated_at, created_at))) as avg_min'))
            ->value('avg_min');

        $avgMinutes = $avgMinutes ? round((float) $avgMinutes, 1) : 0;

        $weeks = TicketMessage::whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%u') as semana"), DB::raw('COUNT(*) as count'))
            ->groupBy('semana')
            ->orderBy('semana')
            ->get();

        $labels = $weeks->pluck('semana')->map(fn ($s) => 'S'.explode('-', $s)[1] ?? $s)->toArray();
        $data = $weeks->pluck('count')->map(fn ($v) => (int) $v)->toArray();

        if (empty($labels)) {
            $labels = [];
            $data = [];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getStockRotacion(): array
    {
        $totalSold = (int) DB::table('order_items')->sum('quantity');
        $stores = DB::table('stores')->count();
        $avgRotation = $stores > 0 ? round($totalSold / max($stores, 1) / 4, 1) : 0;

        return [
            'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
            'data' => [$avgRotation, $avgRotation, $avgRotation, $avgRotation],
        ];
    }

    private function getROI(array $ingresosBrutos): array
    {
        $totalRevenue = array_sum($ingresosBrutos['data'] ?? []);
        $expensesTotal = (float) Expense::sum('amount') ?: 1;
        $roiValue = $expensesTotal > 0 ? round(($totalRevenue - $expensesTotal) / $expensesTotal * 100, 1) : 0;

        return [
            'labels' => ['ROI General'],
            'data' => [$roiValue],
        ];
    }

    private function getLTV(string $start, string $end): array
    {
        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y') as año"), DB::raw('AVG(total) as avg_total'))
            ->groupBy('año')
            ->orderBy('año')
            ->get();

        $labels = $rows->pluck('año')->toArray();
        $data = $rows->pluck('avg_total')->map(fn ($v) => round((float) $v, 2))->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getCuotaMercado(string $start, string $end): array
    {
        $total = (float) Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->sum('total');

        $storesData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('stores', 'order_items.store_id', '=', 'stores.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end.' 23:59:59'])
            ->select('stores.id', 'stores.trade_name', DB::raw('SUM(order_items.line_total) as ventas'))
            ->groupBy('stores.id', 'stores.trade_name')
            ->orderByDesc('ventas')
            ->limit(4)
            ->get();

        $labels = $storesData->pluck('trade_name')->map(fn ($n) => $n ?: 'Sin nombre')->toArray();
        $data = $storesData->map(fn ($s) => $total > 0 ? round((float) $s->ventas / $total * 100, 1) : 0)->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => []];
        }

        $otherShare = 100 - array_sum($data);
        if ($otherShare > 0) {
            $labels[] = 'Otros';
            $data[] = round($otherShare, 1);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getProximoPago(): array
    {
        $pendingPayments = (float) SellerPayment::where('status', 'pending')->sum('net_amount');

        if ($pendingPayments <= 0) {
            $avgOrder = (float) Order::where('payment_status', 'paid')
                ->avg('total');
            $avgCommission = (float) DB::table('stores')->avg('commission_rate') / 100;
            $pendingPayments = round($avgOrder * (1 - $avgCommission), 2);
        }

        $pendingCount = SellerPayment::where('status', 'pending')->count();

        return [
            'value' => $pendingPayments,
            'chart' => [
                'labels' => ['Recaudado', 'Restante'],
                'data' => [$pendingPayments, round($pendingPayments * 0.3, 2)],
            ],
        ];
    }

    private function getTopBuyers(string $start, string $end): array
    {
        $users = User::select('users.id', 'users.name')
            ->selectRaw('SUM(orders.total) as clv')
            ->selectRaw('COUNT(orders.id) as purchases')
            ->selectRaw('MAX(orders.created_at) as last_purchase')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end.' 23:59:59'])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('clv')
            ->limit(5)
            ->get();

        return $users->map(fn ($u) => [
            'id' => (string) $u->id,
            'name' => $u->name,
            'clv' => (float) $u->clv,
            'purchases' => (int) $u->purchases,
            'lastPurchase' => $u->last_purchase ? \Carbon\Carbon::parse($u->last_purchase)->diffForHumans() : 'Nunca',
        ])->toArray();
    }

    private function getHeatmap(string $start, string $end): array
    {
        $rows = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->select(DB::raw("DATE_FORMAT(created_at, '%w') as day_num"),
                DB::raw("DATE_FORMAT(created_at, '%H') as hour"),
                DB::raw('COUNT(*) as value'))
            ->groupBy('day_num', 'hour')
            ->orderBy('day_num')
            ->orderBy('hour')
            ->get();

        $dayNames = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->map(fn ($r) => [
            'day' => $dayNames[(int) $r->day_num] ?? 'Dom',
            'hour' => (int) $r->hour,
            'value' => (int) $r->value,
        ])->toArray();
    }

    private function getCategories(string $start, string $end): array
    {
        $cats = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('category_product', 'products.id', '=', 'category_product.product_id')
            ->join('categories', 'category_product.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end.' 23:59:59'])
            ->select('categories.name', DB::raw('SUM(order_items.line_total) as total'))
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $labels = $cats->pluck('name')->map(fn ($n) => $n ?: 'General')->toArray();
        $data = $cats->pluck('total')->map(fn ($v) => round((float) $v, 2))->toArray();

        if (empty($labels)) {
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getCSAT(string $start, string $end): array
    {
        $avgRating = (float) Ticket::whereNotNull('satisfaction_rating')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->avg('satisfaction_rating');

        $percentage = $avgRating > 0 ? round($avgRating / 5 * 100, 1) : 0;

        return [
            'labels' => ['CSAT'],
            'data' => [$percentage],
        ];
    }

    private function getDesgloseFinanciero(string $start, string $end): array
    {
        $totalConIgv = (float) Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->sum('total');

        $totalIgv = round($totalConIgv * 0.18 / 1.18, 2);
        $baseImponible = $totalConIgv - $totalIgv;

        $avgCommissionRate = (float) DB::table('stores')->avg('commission_rate') / 100;
        $totalCommission = round($baseImponible * $avgCommissionRate, 2);
        $totalNeto = round($baseImponible - $totalCommission, 2);

        $pendingPayments = SellerPayment::where('status', SellerPayment::STATUS_PENDING);
        $completedPayments = SellerPayment::where('status', SellerPayment::STATUS_COMPLETED);

        $totalPending = (float) $pendingPayments->sum('net_amount');
        $pendingCount = $pendingPayments->count();
        $totalCompleted = (float) $completedPayments->sum('net_amount');
        $completedCount = $completedPayments->count();

        return [
            'totalConIgv' => $totalConIgv,
            'totalIgv' => $totalIgv,
            'totalCommission' => $totalCommission,
            'totalNeto' => $totalNeto,
            'totalPending' => $totalPending,
            'totalCompleted' => $totalCompleted,
            'pendingCount' => $pendingCount,
            'completedCount' => $completedCount,
        ];
    }

    private function getComprobantesRecientes(): array
    {
        $invoices = Invoice::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($invoices->isEmpty()) {
            return [];
        }

        return $invoices->map(fn (Invoice $inv) => [
            'id' => (string) $inv->id,
            'series' => $inv->series ?? '—',
            'number' => $inv->number ?? $inv->invoice_number,
            'type' => $inv->document_type ?? 'Factura',
            'sunat_status' => strtoupper($inv->status),
            'total' => (float) $inv->total,
            'emission_date' => $inv->created_at->toDateString(),
        ])->toArray();
    }
}
