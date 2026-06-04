<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpecialistRequest;
use App\Http\Requests\UpdateSpecialistRequest;
use App\Http\Resources\SpecialistResource;
use App\Models\Specialist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SpecialistController
 *
 * CRUD completo para especialistas de una tienda.
 *
 * Todas las rutas requieren auth:sanctum.
 * Las rutas de escritura (store/update/destroy) requieren rol seller o administrator.
 *
 * Rutas sugeridas en api.php:
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │  Route::middleware(['auth:sanctum'])->prefix('specialists')->group(     │
 * │      function () {                                                      │
 * │          Route::get('/',           [SpecialistController::class, 'index']);   │
 * │          Route::post('/',          [SpecialistController::class, 'store']);   │
 * │          Route::get('/{specialist}',    [SpecialistController::class, 'show']);    │
 * │          Route::put('/{specialist}',    [SpecialistController::class, 'update']);  │
 * │          Route::delete('/{specialist}', [SpecialistController::class, 'destroy']); │
 * │      }                                                                  │
 * │  );                                                                     │
 * └─────────────────────────────────────────────────────────────────────────┘
 */
final class SpecialistController extends Controller
{
    // ── INDEX ─────────────────────────────────────────────────────────────────

    /**
     * Lista los especialistas de la tienda del vendedor autenticado.
     *
     * GET /api/specialists
     * Query params:
     *   - availability  → filtra por Disponible|Indispuesto|Ocupado
     *   - especialidad  → búsqueda parcial (LIKE)
     *   - per_page      → paginación (default 15, max 100)
     *   - with_schedules→ si "true", carga los horarios de cada especialista
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->resolveStore($request);

        if (! $store) {
            return $this->storeNotFoundResponse();
        }

        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = Specialist::query()
            ->where('store_id', $store->id)
            ->latest();

        // Filtro por estado de disponibilidad
        if ($availability = $request->query('availability')) {
            $query->where('availability', $availability);
        }

        // Búsqueda por especialidad
        if ($especialidad = $request->query('especialidad')) {
            $query->where('especialidad', 'like', "%{$especialidad}%");
        }

        // Cargar horarios si se solicita explícitamente
        if ($request->boolean('with_schedules')) {
            $query->with('schedules');
        }

        $specialists = $query->paginate($perPage);

        return response()->json([
            'data' => SpecialistResource::collection($specialists->items()),
            'meta' => [
                'current_page' => $specialists->currentPage(),
                'last_page'    => $specialists->lastPage(),
                'per_page'     => $specialists->perPage(),
                'total'        => $specialists->total(),
            ],
        ]);
    }

    // ── STORE ─────────────────────────────────────────────────────────────────

    /**
     * Crea un nuevo especialista para la tienda del vendedor.
     *
     * POST /api/specialists
     * Body: StoreSpecialistRequest
     *
     * El campo `google_calendar_id` se auto-rellena con el email si no se provee
     * (lógica en StoreSpecialistRequest::prepareForValidation).
     */
    public function store(StoreSpecialistRequest $request): JsonResponse
    {
        $store = $this->resolveStore($request);

        if (! $store) {
            return $this->storeNotFoundResponse();
        }

        $specialist = Specialist::create(array_merge(
            $request->validated(),
            ['store_id' => $store->id]
        ));

        return response()->json(
            new SpecialistResource($specialist),
            201
        );
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────

    /**
     * Devuelve el detalle de un especialista.
     *
     * GET /api/specialists/{specialist}
     *
     * Acceso público: clientes pueden ver el especialista sin datos privados.
     * SpecialistResource aplica la censura automáticamente según el rol del usuario.
     */
    public function show(Request $request, int $specialist): JsonResponse
    {
        $model = Specialist::with('schedules')->findOrFail($specialist);

        return response()->json(new SpecialistResource($model));
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    /**
     * Actualiza los datos de un especialista.
     *
     * PUT /api/specialists/{specialist}
     * Body: UpdateSpecialistRequest
     *
     * Solo el vendedor dueño del especialista o un administrador puede actualizar.
     */
    public function update(UpdateSpecialistRequest $request, int $specialist): JsonResponse
    {
        $model = Specialist::findOrFail($specialist);

        if (! $this->userOwnsSpecialist($request, $model)) {
            return $this->unauthorizedResponse();
        }

        $model->update($request->validated());

        return response()->json(new SpecialistResource($model->fresh('schedules')));
    }

    // ── DESTROY ───────────────────────────────────────────────────────────────

    /**
     * Elimina (soft delete) un especialista.
     *
     * DELETE /api/specialists/{specialist}
     *
     * BLOQUEA la eliminación si el especialista tiene reservas futuras
     * pendientes o confirmadas. El vendedor debe cancelarlas primero.
     */
    public function destroy(Request $request, int $specialist): JsonResponse
    {
        $model = Specialist::findOrFail($specialist);

        if (! $this->userOwnsSpecialist($request, $model)) {
            return $this->unauthorizedResponse();
        }

        // Protección: no eliminar si hay citas futuras activas
        if ($model->hasFutureBookings()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar a este especialista porque tiene citas futuras pendientes o confirmadas. Cancélalas primero.',
            ], 422);
        }

        $model->delete(); // SoftDelete

        return response()->json(null, 204);
    }

    // ── HELPERS PRIVADOS ──────────────────────────────────────────────────────

    /**
     * Resuelve la tienda aprobada del vendedor autenticado.
     * Intenta primero la relación directa `store` y luego la relación N:N `stores`.
     */
    private function resolveStore(Request $request): mixed
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return $user->store
            ?? $user->stores()->where('status', 'approved')->first();
    }

    /**
     * Verifica que el usuario autenticado sea dueño del especialista
     * (la tienda del especialista pertenece al vendedor) o sea administrador.
     */
    private function userOwnsSpecialist(Request $request, Specialist $specialist): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('administrator')) {
            return true;
        }

        return $user->stores()->where('stores.id', $specialist->store_id)->exists();
    }

    private function storeNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'No tienes una tienda registrada o aprobada en el sistema.',
        ], 403);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'No tienes acceso a este especialista.',
        ], 403);
    }
}
