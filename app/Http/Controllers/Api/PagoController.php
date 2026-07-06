<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PagoController extends Controller
{
    public function adminHistory(Request $request): JsonResponse
    {
        $query = PlanRequest::query()
            ->with([
                'store.owner:id,name,email',
                'plan:id,name,slug,monthly_fee,css_color',
            ])
            ->whereNotNull('total_amount')
            ->where('total_amount', '>', 0)
            ->orderBy('created_at', 'desc');

        if ($estado = $request->query('estado')) {
            if (in_array($estado, ['paid', 'failed', 'pending'])) {
                $query->where('payment_status', $estado);
            }
        }

        if ($metodo = $request->query('metodo')) {
            $query->where('payment_method', $metodo);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $pagos = $query->paginate($perPage);

        // Totals — single query with conditional aggregation
        $totals = PlanRequest::whereNotNull('total_amount')
            ->where('total_amount', '>', 0)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_monto')
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END), 0) as pagos_exitosos', ['paid'])
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END), 0) as pagos_fallidos', ['failed'])
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END), 0) as pagos_pendientes', ['pending'])
            ->first();

        return response()->json([
            'data' => $pagos->map(fn ($p) => [
                'id' => $p->id,
                'store_id' => $p->store_id,
                'store_name' => $p->store->trade_name,
                'seller_name' => $p->store->owner?->name,
                'seller_email' => $p->store->owner?->email,
                'plan' => [
                    'id' => $p->plan->id,
                    'name' => $p->plan->name,
                    'slug' => $p->plan->slug,
                    'monthly_fee' => $p->plan->monthly_fee,
                    'color' => $p->plan->css_color,
                ],
                'amount' => (float) $p->total_amount,
                'months' => $p->months,
                'payment_method' => $p->payment_method,
                'payment_status' => $p->payment_status,
                'status' => $p->status,
                'created_at' => $p->created_at->toIso8601String(),
                'procesado_en' => $p->updated_at->toIso8601String(),
            ]),
            'totales' => [
                'total_monto' => (float) ($totals->total_monto ?? 0),
                'pagos_exitosos' => (int) ($totals->pagos_exitosos ?? 0),
                'pagos_fallidos' => (int) ($totals->pagos_fallidos ?? 0),
                'pagos_pendientes' => (int) ($totals->pagos_pendientes ?? 0),
            ],
            'pagination' => [
                'page' => $pagos->currentPage(),
                'perPage' => $pagos->perPage(),
                'total' => $pagos->total(),
                'totalPages' => $pagos->lastPage(),
                'hasMore' => $pagos->hasMorePages(),
            ],
        ]);
    }

    public function adminVendedorPagos(Request $request, int $storeId): JsonResponse
    {
        $pagos = PlanRequest::query()
            ->with('plan:id,name,slug,monthly_fee,css_color')
            ->where('store_id', $storeId)
            ->whereNotNull('total_amount')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'estado' => $p->payment_status,
                'monto' => (float) $p->total_amount,
                'meses' => $p->months,
                'fecha' => $p->created_at->toIso8601String(),
                'metodo_pago' => $p->payment_method === 'izipay' ? 'Izipay' : 'Trial',
                'plan_id' => $p->plan->slug,
                'plan_nombre' => $p->plan->name,
                'plan_color' => $p->plan->css_color,
            ]);

        return response()->json(['data' => $pagos]);
    }
}
