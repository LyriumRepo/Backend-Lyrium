<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\StoreStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\StoreStatusNotification;
use App\Notifications\UserBannedNotification;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AdminSellerController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}
    /**
     * GET /api/admin/sellers/stats
     *
     * Devuelve las 4 métricas del panel de vendedores:
     *  - total     : total de usuarios con rol seller
     *  - active    : tiendas con status = 'active'
     *  - pending   : tiendas con status = 'pending'
     *              + profile_requests pendientes sin tienda activa
     *  - alerts    : tiendas con strikes > 0
     *              + tiendas con disputas abiertas (open/under_review)
     *              + tiendas con pagos fallidos pendientes
     */
    public function stats(): JsonResponse
    {
        // IDs de usuarios con rol seller
        $sellerUserIds = User::role('seller')->pluck('id');

        $total = $sellerUserIds->count();

        // Stores pertenecientes a sellers
        $sellerStoreQuery = Store::whereIn('owner_id', $sellerUserIds)
            ->whereNull('deleted_at');

        $active = (clone $sellerStoreQuery)->where('status', 'approved')->count();
        $pending = (clone $sellerStoreQuery)->where('status', 'pending')->count();

        // Alertas: strikes > 0 || disputas abiertas || pagos fallidos
        $storesWithStrikes = (clone $sellerStoreQuery)
            ->where('strikes', '>', 0)
            ->pluck('id');

        $storesWithDisputes = \App\Models\Dispute::whereIn('store_id', $sellerStoreQuery->pluck('id'))
            ->whereIn('status', ['open', 'under_review'])
            ->distinct('store_id')
            ->pluck('store_id');

        $storesWithFailedPayments = \App\Models\SellerPayment::whereIn('store_id', $sellerStoreQuery->pluck('id'))
            ->whereIn('status', ['failed', 'pending'])
            ->distinct('store_id')
            ->pluck('store_id');

        $alertStoreIds = $storesWithStrikes
            ->merge($storesWithDisputes)
            ->merge($storesWithFailedPayments)
            ->unique();

        $alerts = $alertStoreIds->count();

        return response()->json([
            'total' => $total,
            'active' => $active,
            'pending' => $pending,
            'alerts' => $alerts,
        ]);
    }

    /**
     * GET /api/admin/sellers
     *
     * Lista paginada de vendedores con su tienda, contrato,
     * alertas calculadas y estado de verificación.
     *
     * Query params:
     *  - search    : busca en name, email, username, trade_name de tienda
     *  - status    : active | pending | banned | alert
     *  - per_page  : items por página (default 20, max 100)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $search = $request->query('search');
        $status = $request->query('status');

        // Base: usuarios con rol seller
        $query = User::role('seller')
            ->with([
                'ownedStores' => function ($q) {
                    $q->with(['contracts' => function ($c) {
                        $c->where('status', 'ACTIVE')->latest('created_at');
                    }])
                        ->whereNull('deleted_at');
                },
            ])
            ->withCount('ownedStores');

        // Búsqueda en usuario O en nombre de tienda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('ownedStores', function ($s) use ($search) {
                        $s->where('trade_name', 'like', "%{$search}%")
                            ->orWhere('store_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filtro por status
        match ($status) {
            'active' => $query->where('is_banned', false)
                ->whereHas('ownedStores', fn ($s) => $s->where('status', 'active')),
            'pending' => $query->whereHas('ownedStores', fn ($s) => $s->where('status', 'pending')),
            'banned' => $query->where('is_banned', true),
            'alert' => $query->whereHas('ownedStores', fn ($s) => $s->where('strikes', '>', 0)),
            default => null,
        };

        $users = $query->latest('created_at')->paginate($perPage);

        // Enriquecer cada usuario con datos de su tienda principal
        $data = $users->getCollection()->map(function (User $user) {
            $store = $user->ownedStores->first();

            $alertReasons = [];

            if ($store) {
                if ($store->strikes > 0) {
                    $alertReasons[] = 'strikes';
                }

                $hasOpenDispute = \App\Models\Dispute::where('store_id', $store->id)
                    ->whereIn('status', ['open', 'under_review'])
                    ->exists();

                if ($hasOpenDispute) {
                    $alertReasons[] = 'disputes';
                }

                $hasFailedPayment = \App\Models\SellerPayment::where('store_id', $store->id)
                    ->where('status', 'failed')
                    ->exists();

                if ($hasFailedPayment) {
                    $alertReasons[] = 'failed_payment';
                }
            }

            return [
                'id' => $user->id,
                'username' => $user->username,
                'display_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'document_type' => $user->document_type,
                'document_number' => $user->document_number,
                'avatar' => $user->avatar,
                'is_banned' => $user->is_banned,
                'email_verified' => (bool) $user->email_verified_at,
                'created_at' => $user->created_at?->toIso8601String(),
                'stores_count' => $user->owned_stores_count,
                'store' => $store ? [
                    'id' => $store->id,
                    'trade_name' => $store->trade_name,
                    'store_name' => $store->store_name,
                    'slug' => $store->slug,
                    'logo' => $store->logo,
                    'status' => $store->status,
                    'profile_status' => $store->profile_status,
                    'strikes' => $store->strikes,
                    'rating' => $store->average_rating,
                    'review_count' => $store->review_count,
                    'total_sales' => OrderItem::where('store_id', $store->id)
                        ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled']))
                        ->count(),
                    'approved_at' => $store->approved_at?->toIso8601String(),
                    'has_active_contract' => $store->contracts->isNotEmpty(),
                ] : null,
                'alerts' => $alertReasons,   // [] = sin alerta
                'has_alerts' => count($alertReasons) > 0,
            ];
        });

        return response()->json([
            'data' => $data,
            'pagination' => [
                'page' => $users->currentPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
                'totalPages' => $users->lastPage(),
                'hasMore' => $users->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/admin/sellers/{id}
     *
     * Detalle completo de un vendedor (para el modal o vista de perfil).
     */
    public function show(int $id): JsonResponse
    {
        $user = User::role('seller')
            ->with([
                'ownedStores.contracts',
                'ownedStores.subscription',
            ])
            ->findOrFail($id);

        $store = $user->ownedStores->first();

        $openDisputes = $store
            ? \App\Models\Dispute::where('store_id', $store->id)
                ->whereIn('status', ['open', 'under_review'])
                ->count()
            : 0;

        $pendingPayments = $store
            ? \App\Models\SellerPayment::where('store_id', $store->id)
                ->where('status', 'pending')
                ->count()
            : 0;

        $totalSales = $store
            ? OrderItem::where('store_id', $store->id)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled']))
                ->count()
            : 0;

        $storeData = $store ? array_merge($store->toArray(), [
            'rating' => $store->average_rating,
            'review_count' => $store->review_count,
            'total_sales' => $totalSales,
        ]) : null;

        return response()->json([
            'user' => new UserResource($user),
            'store' => $storeData,
            'open_disputes' => $openDisputes,
            'pending_payments' => $pendingPayments,
        ]);
    }

    /**
     * PUT /api/admin/sellers/{id}/ban
     *
     * Banea o desbanea un vendedor (toggle).
     * Reutiliza la misma lógica que UserController@toggleBan
     * pero asegura que sea un seller.
     */
    public function toggleBan(Request $request, int $id): JsonResponse
    {
        $user = User::role('seller')->findOrFail($id);
        $reason = $request->input('reason');

        $wasBanned = $user->is_banned;
        $user->update(['is_banned' => ! $wasBanned]);

        $isBanned = !$wasBanned;
        $this->auditService->record(
            event: $isBanned ? 'users.banned' : 'users.unbanned',
            module: 'users',
            description: $isBanned ? 'Vendedor bloqueado' : 'Vendedor habilitado',
            auditable: $user,
            oldValues: ['is_banned' => $wasBanned],
            newValues: ['is_banned' => $isBanned],
            source: AuditService::SOURCE_WEB,
            metadata: ['user_id' => $user->id, 'banned_by' => auth()->id(), 'seller_name' => $user->name],
        );

        if ($user->is_banned) {
            try {
                $user->notify(new UserBannedNotification($reason));
            } catch (\Throwable $e) {
                Log::error('[AdminSeller] Error notificando UserBanned', [
                    'user_id' => $user->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'id' => $user->id,
            'is_banned' => $user->is_banned,
            'message' => $user->is_banned
                ? 'Vendedor baneado correctamente.'
                : 'Vendedor habilitado correctamente.',
        ]);
    }

    /**
     * GET /api/admin/vendedores
     *
     * Lista tiendas con info de suscripción activa (panel de planes admin).
     */
    public function vendedores(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $search = $request->query('search');
        $planFilter = $request->query('plan_filter');

        $query = Store::whereNull('deleted_at')
            ->with([
                'owner:id,name,email',
                'activeSubscription' => fn ($q) => $q->with('plan:id,name,slug,css_color,monthly_fee'),
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('trade_name', 'like', "%{$search}%")
                  ->orWhereHas('owner', fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($planFilter) {
            if ($planFilter === 'sin_plan') {
                $query->whereDoesntHave('activeSubscription');
            } else {
                $query->whereHas('activeSubscription.plan', fn ($q) => $q->where('slug', $planFilter));
            }
        }

        $stores = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $stores->getCollection()->map(function (Store $store) {
            $sub = $store->activeSubscription;
            $plan = $sub?->plan;

            return [
                'id'             => $store->id,
                'store_id'       => $store->id,
                'trade_name'     => $store->trade_name,
                'slug'           => $store->slug ?? '',
                'status'         => $store->status,
                'ruc'            => $store->ruc ?? '',
                'commission_rate'=> (string) ($store->commission_rate ?? '0'),
                'strikes'        => $store->strikes ?? 0,
                'seller'         => $store->owner ? [
                    'id'    => $store->owner->id,
                    'name'  => $store->owner->name,
                    'email' => $store->owner->email,
                ] : null,
                'subscription'   => $sub ? [
                    'id'          => $sub->id,
                    'plan_id'     => $sub->plan_id,
                    'plan_name'   => $plan?->name,
                    'plan_slug'   => $plan?->slug,
                    'plan_color'  => $plan?->css_color ?? '#10b981',
                    'monthly_fee' => (string) ($plan?->monthly_fee ?? '0'),
                    'starts_at'   => $sub->starts_at?->toIso8601String(),
                    'ends_at'     => $sub->ends_at?->toIso8601String(),
                    'is_active'   => $sub->status === 'active',
                ] : null,
                'created_at'     => $store->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page'       => $stores->currentPage(),
                'perPage'    => $stores->perPage(),
                'total'      => $stores->total(),
                'totalPages' => $stores->lastPage(),
                'hasMore'    => $stores->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/admin/vendedores/{id}/historial
     *
     * Historial de suscripciones de una tienda.
     */
    public function vendedorHistorial(int $id): JsonResponse
    {
        $store = Store::findOrFail($id);

        $subscriptions = Subscription::where('store_id', $store->id)
            ->with('plan:id,name,slug,monthly_fee,css_color')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions->map(fn (Subscription $sub) => [
                'plan_id'    => $sub->plan_id,
                'plan_name'  => $sub->plan?->name,
                'plan_slug'  => $sub->plan?->slug,
                'monthly_fee'=> (string) ($sub->plan?->monthly_fee ?? '0'),
                'starts_at'  => $sub->starts_at?->toIso8601String(),
                'ends_at'    => $sub->ends_at?->toIso8601String(),
                'status'     => $sub->status,
            ]),
        ]);
    }

    /**
     * PUT /api/admin/sellers/{storeId}/store-status
     *
     * Cambia el status de la tienda: pending → active | suspended
     */
    public function updateStoreStatus(Request $request, int $storeId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,pending,suspended',
            'reason' => 'nullable|string|max:500',
        ]);

        $store = Store::with('owner')->whereNull('deleted_at')->findOrFail($storeId);
        $store->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'active' ? now() : $store->approved_at,
        ]);

        if ($store->owner) {
            try {
                $store->owner->notify(new StoreStatusNotification(
                    $store,
                    $validated['status'],
                    $validated['reason'] ?? null,
                ));
            } catch (\Throwable $e) {
                Log::error('[AdminSeller] Error notificando StoreStatus', [
                    'store_id' => $store->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        broadcast(new StoreStatusChanged($store->fresh()));

        return response()->json([
            'store_id' => $store->id,
            'status' => $store->status,
            'message' => 'Estado de tienda actualizado.',
        ]);
    }
}
