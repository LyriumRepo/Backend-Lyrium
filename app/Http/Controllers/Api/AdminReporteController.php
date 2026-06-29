<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\GenericExport;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\IzipayOrderTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

final class AdminReporteController extends Controller
{
    // ──────────────────────────────────────────────
    // 1. REPORTE DE VENTAS
    // ──────────────────────────────────────────────

    public function ventas(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Order::query()
            ->whereHas('izipayTransactions')
            ->with(['user', 'items.store', 'latestIzipayTransaction'])
            ->latest();

        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('created_at', '<=', $dateTo);

        $orders = $query->get();

        $totalAmount = $orders->sum('total');
        $totalOrders = $orders->count();
        $paidOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->count();
        $failedOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_FAILED)->count();
        $pendingOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PENDING)->count();

        $methodDistribution = IzipayOrderTransaction::query()
            ->select('payment_method_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount_in_cents) as total_in_cents'))
            ->whereIn('order_id', $orders->pluck('id'))
            ->groupBy('payment_method_type')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method_type,
                'count' => (int) $row->count,
                'totalInCents' => (int) $row->total_in_cents,
            ]);

        $dailyTotals = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
            ->map(fn ($group) => [
                'date' => $group->first()->created_at->format('d/m/Y'),
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ])->values();

        $format = $request->input('format', 'pdf');

        return match ($format) {
            'csv' => $this->csv('reporte-ventas', ['#', 'Orden', 'Fecha', 'Cliente', 'Email', 'Total', 'Metodo', 'Estado Pago', 'Estado Transaccion'], $orders->map(fn ($o, $i) => [
                $i + 1, $o->order_number, $o->created_at->format('d/m/Y H:i'),
                $o->user?->name ?? 'N/A', $o->user?->email ?? 'N/A',
                number_format($o->total, 2),
                $o->latestIzipayTransaction?->payment_method_type ?? '—',
                $o->payment_status,
                $o->latestIzipayTransaction?->transaction_status ?? '—',
            ])->toArray()),
            'excel' => Excel::download(new GenericExport(
                ['#', 'Orden', 'Fecha', 'Cliente', 'Email', 'Total', 'Metodo', 'Estado Pago', 'Estado Transaccion'],
                $orders->map(fn ($o, $i) => [
                    $i + 1, $o->order_number, $o->created_at->format('d/m/Y H:i'),
                    $o->user?->name ?? 'N/A', $o->user?->email ?? 'N/A',
                    (float) $o->total, $o->latestIzipayTransaction?->payment_method_type ?? '—',
                    $o->payment_status, $o->latestIzipayTransaction?->transaction_status ?? '—',
                ])->toArray()
            ), 'reporte-ventas.xlsx'),
            default => Pdf::loadView('pdf.reporte-ventas', compact('orders', 'totalAmount', 'totalOrders', 'paidOrders', 'failedOrders', 'pendingOrders', 'methodDistribution', 'dailyTotals'))
                ->download('reporte-ventas.pdf'),
        };
    }

    // ──────────────────────────────────────────────
    // 2. REPORTE DE VENDEDORES
    // ──────────────────────────────────────────────

    public function vendedores(Request $request)
    {
        $stores = Store::query()
            ->with(['user', 'subscriptions.plan'])
            ->withCount(['products', 'services'])
            ->get()
            ->map(function ($store) {
                $totalSold = Order::whereHas('items', fn ($q) => $q->where('store_id', $store->id))
                    ->where('payment_status', Order::PAYMENT_STATUS_PAID)
                    ->sum('total');

                $activeSub = $store->subscriptions()->where('status', 'active')->with('plan')->first();
                $rate = $activeSub?->plan?->commission_rate ?? 0;
                $commissionBase = $totalSold > 0 ? $totalSold / 1.18 : 0;
                $commissionTotal = $commissionBase * $rate;

                return [
                    'store_name' => $store->store_name,
                    'owner' => $store->user?->name ?? 'N/A',
                    'email' => $store->user?->email ?? 'N/A',
                    'status' => $store->status,
                    'products_count' => $store->products_count,
                    'services_count' => $store->services_count,
                    'total_sold' => round($totalSold, 2),
                    'commission' => round($commissionTotal, 2),
                    'plan' => $activeSub?->plan?->name ?? 'Sin plan',
                ];
            });

        $format = $request->input('format', 'pdf');

        return match ($format) {
            'csv' => $this->csv('reporte-vendedores', ['#', 'Tienda', 'Propietario', 'Email', 'Estado', 'Productos', 'Servicios', 'Total Vendido', 'Comision', 'Plan'], $stores->map(fn ($s, $i) => [
                $i + 1, $s['store_name'], $s['owner'], $s['email'], $s['status'],
                $s['products_count'], $s['services_count'], number_format($s['total_sold'], 2),
                number_format($s['commission'], 2), $s['plan'],
            ])->toArray()),
            'excel' => Excel::download(new GenericExport(
                ['#', 'Tienda', 'Propietario', 'Email', 'Estado', 'Productos', 'Servicios', 'Total Vendido', 'Comision', 'Plan'],
                $stores->map(fn ($s, $i) => [
                    $i + 1, $s['store_name'], $s['owner'], $s['email'], $s['status'],
                    $s['products_count'], $s['services_count'], (float) $s['total_sold'],
                    (float) $s['commission'], $s['plan'],
                ])->toArray()
            ), 'reporte-vendedores.xlsx'),
            default => Pdf::loadView('pdf.reporte-vendedores', ['stores' => $stores])
                ->download('reporte-vendedores.pdf'),
        };
    }

    // ──────────────────────────────────────────────
    // 3. REPORTE FINANCIERO
    // ──────────────────────────────────────────────

    public function financiero(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $orderQuery = Order::query()->whereHas('izipayTransactions');
        $expenseQuery = Expense::query();
        $invoiceQuery = Invoice::query();

        if ($dateFrom) {
            $orderQuery->whereDate('created_at', '>=', $dateFrom);
            $expenseQuery->whereDate('created_at', '>=', $dateFrom);
            $invoiceQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $orderQuery->whereDate('created_at', '<=', $dateTo);
            $expenseQuery->whereDate('created_at', '<=', $dateTo);
            $invoiceQuery->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $orderQuery->get();
        $expenses = $expenseQuery->get();
        $invoices = $invoiceQuery->get();

        $totalIncome = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('total');
        $totalExpenses = $expenses->sum('amount');
        $netIncome = $totalIncome - $totalExpenses;
        $totalInvoiced = $invoices->sum('total');
        $totalOrdersCount = $orders->count();
        $paidOrdersCount = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->count();

        $summary = compact('totalIncome', 'totalExpenses', 'netIncome', 'totalInvoiced', 'totalOrdersCount', 'paidOrdersCount');

        $format = $request->input('format', 'pdf');

        return match ($format) {
            'csv' => $this->csv('reporte-financiero', ['Concepto', 'Valor'], [
                ['Ingresos (ordenes pagadas)', number_format($totalIncome, 2)],
                ['Gastos', number_format($totalExpenses, 2)],
                ['Ingreso Neto', number_format($netIncome, 2)],
                ['Facturado', number_format($totalInvoiced, 2)],
                ['Total Ordenes', (string) $totalOrdersCount],
                ['Ordenes Pagadas', (string) $paidOrdersCount],
            ]),
            'excel' => Excel::download(new GenericExport(
                ['Concepto', 'Valor'],
                [
                    ['Ingresos (ordenes pagadas)', $totalIncome],
                    ['Gastos', $totalExpenses],
                    ['Ingreso Neto', $netIncome],
                    ['Facturado', $totalInvoiced],
                    ['Total Ordenes', $totalOrdersCount],
                    ['Ordenes Pagadas', $paidOrdersCount],
                ]
            ), 'reporte-financiero.xlsx'),
            default => Pdf::loadView('pdf.reporte-financiero', compact('orders', 'expenses', 'invoices', 'summary'))
                ->download('reporte-financiero.pdf'),
        };
    }

    // ──────────────────────────────────────────────
    // 4. REPORTE DE PRODUCTOS / SERVICIOS
    // ──────────────────────────────────────────────

    public function productos(Request $request)
    {
        $products = Product::query()->with('store', 'categories')
            ->withCount('reviews')
            ->get()
            ->map(function ($p) {
                $soldQty = OrderItem::where('product_id', $p->id)
                    ->whereHas('order', fn ($q) => $q->where('payment_status', Order::PAYMENT_STATUS_PAID))
                    ->sum('quantity');
                return [
                    'name' => $p->name,
                    'store' => $p->store?->store_name ?? 'N/A',
                    'category' => $p->categories->pluck('name')->implode(', '),
                    'price' => $p->price,
                    'status' => $p->status,
                    'sold_qty' => (int) $soldQty,
                    'reviews_count' => $p->reviews_count,
                    'rating' => round($p->reviews()->avg('rating') ?? 0, 1),
                ];
            })->sortByDesc('sold_qty')->values();

        $services = Service::query()->with('store', 'category')
            ->withCount('bookings')
            ->get()
            ->map(function ($s) {
                return [
                    'name' => $s->name,
                    'store' => $s->store?->store_name ?? 'N/A',
                    'category' => $s->category?->name ?? 'N/A',
                    'price' => $s->price,
                    'status' => $s->status,
                    'bookings_count' => $s->bookings_count,
                ];
            })->sortByDesc('bookings_count')->values();

        $format = $request->input('format', 'pdf');

        return match ($format) {
            'csv' => $this->csv('reporte-productos',
                ['#', 'Producto', 'Tienda', 'Categoria', 'Precio', 'Estado', 'Vendidos', 'Reviews', 'Rating'],
                $products->map(fn ($p, $i) => [
                    $i + 1, $p['name'], $p['store'], $p['category'], number_format($p['price'], 2),
                    $p['status'], $p['sold_qty'], $p['reviews_count'], $p['rating'],
                ])->toArray()
            ),
            'excel' => Excel::download(new GenericExport(
                ['#', 'Producto', 'Tienda', 'Categoria', 'Precio', 'Estado', 'Vendidos', 'Reviews', 'Rating'],
                $products->map(fn ($p, $i) => [
                    $i + 1, $p['name'], $p['store'], $p['category'], (float) $p['price'],
                    $p['status'], (int) $p['sold_qty'], (int) $p['reviews_count'], (float) $p['rating'],
                ])->toArray()
            ), 'reporte-productos.xlsx'),
            default => Pdf::loadView('pdf.reporte-productos', compact('products', 'services'))
                ->download('reporte-productos.pdf'),
        };
    }

    // ──────────────────────────────────────────────
    // Helper: CSV export
    // ──────────────────────────────────────────────

    private function csv(string $filename, array $headers, array $rows)
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ]);
    }
}
