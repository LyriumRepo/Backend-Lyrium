<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\IzipayOrderTransaction;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TransactionController extends Controller
{
    private function buildFilteredQuery(Request $request)
    {
        $query = Order::query()
            ->whereHas('izipayTransactions')
            ->with(['user', 'items.store', 'items.product', 'latestIzipayTransaction'])
            ->latest();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('transaction_status')) {
            $query->whereHas('izipayTransactions', fn ($q) => $q->where('transaction_status', $request->transaction_status)
            );
        }

        if ($request->filled('payment_method')) {
            $query->whereHas('izipayTransactions', fn ($q) => $q->where('payment_method_type', $request->payment_method)
            );
        }

        if ($request->filled('store_id')) {
            $query->whereHas('items', fn ($q) => $q->where('store_id', $request->store_id)
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                    );
            });
        }

        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $orders = $this->buildFilteredQuery($request)->paginate($perPage);

        return response()->json([
            'data' => TransactionResource::collection($orders),
            'pagination' => [
                'page' => $orders->currentPage(),
                'perPage' => $orders->perPage(),
                'total' => $orders->total(),
                'totalPages' => $orders->lastPage(),
                'hasMore' => $orders->hasMorePages(),
            ],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $orders = $this->buildFilteredQuery($request)->get();

        $headers = ['#', 'Orden', 'Fecha', 'Cliente', 'Email', 'Total', 'Método Pago', 'Estado Pago', 'Estado Transacción'];
        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['#', 'Orden', 'Fecha', 'Cliente', 'Email', 'Total', 'Metodo Pago', 'Estado Pago', 'Estado Transaccion']);
            foreach ($orders as $i => $order) {
                fputcsv($file, [
                    $i + 1,
                    $order->order_number,
                    $order->created_at->format('d/m/Y H:i'),
                    $order->user?->name ?? 'N/A',
                    $order->user?->email ?? 'N/A',
                    number_format($order->total, 2),
                    $order->latestIzipayTransaction?->payment_method_type ?? '—',
                    $order->payment_status,
                    $order->latestIzipayTransaction?->transaction_status ?? '—',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reporte-transacciones.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $orders = $this->buildFilteredQuery($request)->get();
        $pdf = Pdf::loadView('pdf.transactions', ['orders' => $orders]);
        return $pdf->download('reporte-transacciones.pdf');
    }

    public function show(int $id): TransactionResource
    {
        $order = Order::query()
            ->whereHas('izipayTransactions')
            ->with(['user', 'items.store', 'items.product', 'latestIzipayTransaction'])
            ->findOrFail($id);

        return new TransactionResource($order);
    }

    public function stats(): JsonResponse
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        $todayStats = $this->periodStats($todayStart);
        $weekStats = $this->periodStats($weekStart);
        $monthStats = $this->periodStats($monthStart);
        $overallStats = $this->periodStats(null);

        $methodDistribution = IzipayOrderTransaction::query()
            ->select('payment_method_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount_in_cents) as total_in_cents'))
            ->groupBy('payment_method_type')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method_type,
                'count' => (int) $row->count,
                'totalInCents' => (int) $row->total_in_cents,
            ]);

        return response()->json([
            'today' => $todayStats,
            'thisWeek' => $weekStats,
            'thisMonth' => $monthStats,
            'overall' => $overallStats,
            'methodDistribution' => $methodDistribution,
        ]);
    }

    private function periodStats($since): array
    {
        $query = Order::query()->whereHas('izipayTransactions');

        if ($since !== null) {
            $query->where('created_at', '>=', $since);
        }

        $totalTransactions = (clone $query)->count();

        $successful = (clone $query)->where('payment_status', Order::PAYMENT_STATUS_PAID)->count();

        $totalAmount = (clone $query)->sum('total');

        $failed = (clone $query)->where('payment_status', Order::PAYMENT_STATUS_FAILED)->count();

        return [
            'totalTransactions' => $totalTransactions,
            'successful' => $successful,
            'failed' => $failed,
            'totalAmount' => (float) $totalAmount,
            'successRate' => $totalTransactions > 0
                ? round(($successful / $totalTransactions) * 100, 1)
                : 0,
        ];
    }
}
