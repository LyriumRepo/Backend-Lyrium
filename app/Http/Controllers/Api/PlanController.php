<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\AdminPlanResource;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $planService
    ) {}

    // ── Público ───────────────────────────────────

    public function index(): AnonymousResourceCollection
    {
        $plans = Plan::active()->orderBy('monthly_fee')->get();

        return PlanResource::collection($plans);
    }

    public function show(int $id): PlanResource
    {
        $plan = Plan::findOrFail($id);

        return new PlanResource($plan);
    }

    // ── Admin CRUD ────────────────────────────────

    // Usa route model binding: {plan:slug} → se resuelve por slug automáticamente

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $plans = Plan::withCount('subscriptions')
            ->orderBy('monthly_fee')
            ->get();

        return AdminPlanResource::collection($plans);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->planService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Plan creado correctamente',
            'data' => new AdminPlanResource($plan),
        ], 201);
    }

    public function adminShow(Plan $plan): AdminPlanResource
    {
        $plan->loadCount('subscriptions');

        return new AdminPlanResource($plan);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $updated = $this->planService->update($plan->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Plan actualizado correctamente',
            'data' => new AdminPlanResource($updated),
        ]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        try {
            $this->planService->delete($plan->id);

            return response()->json([
                'success' => true,
                'message' => 'Plan eliminado correctamente',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function toggleActive(Plan $plan): JsonResponse
    {
        $updated = $this->planService->toggleActive($plan->id);

        return response()->json([
            'message' => $updated->is_active ? 'Plan activado' : 'Plan desactivado',
            'data' => new PlanResource($updated),
        ]);
    }

    public function setActive(Request $request, Plan $plan): JsonResponse
    {
        $request->validate(['activo' => ['required', 'boolean']]);

        $plan->update(['is_active' => $request->boolean('activo')]);
        $plan->refresh();

        return response()->json([
            'success' => true,
            'message' => $plan->is_active ? 'Plan activado' : 'Plan desactivado',
            'data' => new PlanResource($plan),
        ]);
    }

    public function updateIcon(Request $request, Plan $plan): JsonResponse
    {
        $iconValue = $request->input('icon') ?? $request->input('icono');

        if (empty($iconValue)) {
            return response()->json(['message' => 'El ícono es requerido'], 422);
        }

        $updated = $this->planService->updateIcon($plan->id, $iconValue);

        return response()->json([
            'success' => true,
            'message' => 'Icono actualizado correctamente',
            'data' => new PlanResource($updated),
        ]);
    }

    // ── Configuración de colores ──────────────────

    public function getColors(): JsonResponse
    {
        return response()->json([
            'data' => $this->planService->getButtonColors(),
        ]);
    }

    public function saveColors(Request $request): JsonResponse
    {
        $request->validate([
            'subscribeBg' => ['required', 'string', 'max:20'],
            'subscribeColor' => ['required', 'string', 'max:20'],
            'currentBg' => ['required', 'string', 'max:20'],
            'currentColor' => ['required', 'string', 'max:20'],
            'lockedBg' => ['required', 'string', 'max:20'],
            'lockedColor' => ['required', 'string', 'max:20'],
            'warningColor' => ['required', 'string', 'max:20'],
        ]);

        $colors = $this->planService->saveButtonColors($request->only([
            'subscribeBg',
            'subscribeColor',
            'currentBg',
            'currentColor',
            'lockedBg',
            'lockedColor',
            'warningColor',
        ]));

        return response()->json([
            'message' => 'Colores guardados correctamente',
            'data' => $colors,
        ]);
    }

    public function resetColors(): JsonResponse
    {
        $colors = $this->planService->resetButtonColors();

        return response()->json([
            'message' => 'Colores restaurados a valores por defecto',
            'data' => $colors,
        ]);
    }
}
