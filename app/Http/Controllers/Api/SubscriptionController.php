<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $store = $request->user()->stores()->firstOrFail();

        $subscriptions = Subscription::query()
            ->where('store_id', $store->id)
            ->with(['plan'])
            ->orderBy('created_at', 'desc')
            ->get();

        return SubscriptionResource::collection($subscriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $store = $request->user()->stores()->firstOrFail();
        $plan = Plan::findOrFail($request->validated('plan_id'));

        $activeSubscription = Subscription::query()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'message' => 'Ya tienes una suscripción activa',
                'subscription' => new SubscriptionResource($activeSubscription->load('plan')),
            ], 422);
        }

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        // Vincular end_date del contrato activo con la suscripción
        $activeContract = $store->contracts()->whereIn('status', ['ACTIVE', 'PENDING'])->latest()->first();
        if ($activeContract) {
            $activeContract->update(['end_date' => $subscription->ends_at->toDateString()]);
            $activeContract->addAuditEntry(
                "Vigencia del contrato actualizada por suscripción al plan {$plan->name}",
                'Sistema'
            );
        }

        return (new SubscriptionResource($subscription->load('plan')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id, Request $request): SubscriptionResource
    {
        $store = $request->user()->stores()->firstOrFail();

        $subscription = Subscription::query()
            ->where('store_id', $store->id)
            ->where('id', $id)
            ->with(['plan'])
            ->firstOrFail();

        return new SubscriptionResource($subscription);
    }

    public function cancel(int $id, Request $request): JsonResponse
    {
        $store = $request->user()->stores()->firstOrFail();

        $subscription = Subscription::query()
            ->where('store_id', $store->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($subscription->status === 'cancelled') {
            return response()->json([
                'message' => 'La suscripción ya está cancelada',
            ], 422);
        }

        $subscription->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Suscripción cancelada correctamente',
            'subscription' => new SubscriptionResource($subscription->fresh('plan')),
        ]);
    }

    public function renew(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['sometimes', 'integer', 'exists:plans,id'],
        ]);

        $store = $request->user()->stores()->firstOrFail();

        $subscription = Subscription::query()
            ->where('store_id', $store->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($subscription->status === 'active' && $subscription->ends_at?->isFuture()) {
            return response()->json([
                'message' => 'La suscripción ya está activa',
                'subscription' => new SubscriptionResource($subscription->load('plan')),
            ], 422);
        }

        $planId = $request->validated('plan_id') ?? $subscription->plan_id;
        $plan = Plan::findOrFail($planId);

        $subscription->update([
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        // Vincular end_date del contrato con la renovación
        $activeContract = $store->contracts()->whereIn('status', ['ACTIVE', 'PENDING'])->latest()->first();
        if ($activeContract) {
            $activeContract->update(['end_date' => $subscription->fresh()->ends_at->toDateString()]);
            $activeContract->addAuditEntry(
                "Vigencia del contrato renovada por suscripción al plan {$plan->name}",
                'Sistema'
            );
        }

        return response()->json([
            'message' => 'Suscripción renovada correctamente',
            'subscription' => new SubscriptionResource($subscription->fresh(['plan'])),
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        // Buscar tienda por owner_id en lugar de usar la relación stores()
        $store = \App\Models\Store::where('owner_id', $request->user()->id)->first();

        if (! $store) {
            return response()->json([
                'message' => 'No tienes una tienda registrada',
                'data' => null,
            ]);
        }

        $subscription = Subscription::query()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->with(['plan'])
            ->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'No tienes suscripción activa',
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => new SubscriptionResource($subscription),
        ]);
    }
}
