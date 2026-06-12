<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendedorListResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AdminVendedorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Store::query()
            ->whereIn('owner_id', User::role('seller')->pluck('id'))
            ->whereNull('deleted_at')
            ->with(['owner:id,name,email'])
            ->withCount('strikes as strikes_count');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trade_name', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'pending', 'approved', 'rejected', 'banned'])) {
                $query->where('status', $status);
            }
        }

        if ($plan_filter = $request->query('plan_filter')) {
            if ($plan_filter === 'sin_plan') {
                $query->whereDoesntHave('subscriptions', fn ($s) => $s->where('status', 'active')->where('ends_at', '>=', now()));
            } elseif ($plan_filter === 'activo') {
                $query->whereHas('subscriptions', fn ($s) => $s->where('status', 'active')->where('ends_at', '>=', now()));
            } elseif ($plan_filter === 'por_vencer') {
                $query->whereHas('subscriptions', fn ($s) => $s->where('status', 'active')->where('ends_at', '>=', now())->where('ends_at', '<=', now()->addDays(30)));
            } elseif ($plan_filter === 'vencido') {
                $query->whereHas('subscriptions', fn ($s) => $s->where('status', 'active')->where('ends_at', '<', now()));
            }
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $stores = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return VendedorListResource::collection($stores);
    }

    public function show(int $id): JsonResponse
    {
        $store = Store::whereIn('owner_id', User::role('seller')->pluck('id'))
            ->with(['owner:id,name,email,phone', 'subscriptions.plan'])
            ->findOrFail($id);

        $subscriptions = $store->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        $planRequests = $store->planRequests()
            ->with(['plan:id,name,monthly_fee', 'reviewer:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'store' => new VendedorListResource($store),
                'subscriptions' => $subscriptions->map(fn ($s) => [
                    'id' => $s->id,
                    'plan_name' => $s->plan->name,
                    'status' => $s->status,
                    'starts_at' => $s->starts_at?->toIso8601String(),
                    'ends_at' => $s->ends_at?->toIso8601String(),
                    'is_active' => $s->isActive(),
                ]),
                'plan_requests' => $planRequests->map(fn ($r) => [
                    'id' => $r->id,
                    'plan_name' => $r->plan->name,
                    'status' => $r->status,
                    'payment_method' => $r->payment_method,
                    'payment_status' => $r->payment_status,
                    'total_amount' => $r->total_amount,
                    'admin_notes' => $r->admin_notes,
                    'reviewed_by' => $r->reviewer?->name,
                    'created_at' => $r->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        $sellerUserIds = User::role('seller')->pluck('id');

        $storeQuery = Store::whereIn('owner_id', $sellerUserIds)->whereNull('deleted_at');

        $total = $storeQuery->count();
        $active = (clone $storeQuery)->where('status', 'approved')->count();
        $pending = (clone $storeQuery)->where('status', 'pending')->count();

        $conPlan = (clone $storeQuery)->whereHas('subscriptions', fn ($s) => $s->where('status', 'active')->where('ends_at', '>=', now()))->count();
        $sinPlan = $total - $conPlan;

        return response()->json([
            'data' => [
                'total' => $total,
                'active' => $active,
                'pending' => $pending,
                'con_plan' => $conPlan,
                'sin_plan' => $sinPlan,
            ],
        ]);
    }
}
