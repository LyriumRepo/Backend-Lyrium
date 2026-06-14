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
 * SpecialistController v2
 *
 * CAMBIOS:
 *   - index()     → nuevo filtro ?category_id= y ?parent_category_id=
 *   - store()     → persiste category_id
 *   - byCategory()→ nuevo endpoint público para obtener especialistas
 *                   de una categoría nivel 2 (usado al crear un servicio)
 *
 * Rutas sugeridas en api.php:
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │  // Público: especialistas por categoría (para el formulario de servicio)│
 * │  Route::get('/specialists/by-category/{categoryId}',                    │
 * │      [SpecialistController::class, 'byCategory']);                       │
 * │                                                                          │
 * │  Route::middleware(['auth:sanctum'])->prefix('stores/me')->group(        │
 * │      function () {                                                        │
 * │          Route::get('/specialists',           [SpecialistController::class, 'index']);   │
 * │          Route::post('/specialists',          [SpecialistController::class, 'store']);   │
 * │          Route::get('/specialists/{specialist}',    [SpecialistController::class, 'show']);    │
 * │          Route::put('/specialists/{id}',    [SpecialistController::class, 'update']);  │
 * │          Route::delete('/specialists/{id}', [SpecialistController::class, 'destroy']); │
 * │      }                                                                   │
 * │  );                                                                      │
 * └──────────────────────────────────────────────────────────────────────────┘
 */
final class SpecialistController extends Controller
{
    // ── INDEX ─────────────────────────────────────────────────────────────────

    /**
     * Lista los especialistas de la tienda del vendedor autenticado.
     *
     * GET /api/stores/me/specialists
     * Query params:
     *   - availability        → Disponible|Indispuesto|Ocupado
     *   - especialidad        → búsqueda parcial (LIKE)
     *   - category_id         → filtro por categoría nivel 2
     *   - parent_category_id  → filtro por categoría nivel 1 (devuelve todos sus hijos)
     *   - per_page            → paginación (default 15, max 100)
     *   - with_schedules      → si "true", carga los horarios
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
            ->with('category.parent') // carga categoría y su padre (nivel 1)
            ->latest();

        // Filtro por estado de disponibilidad
        if ($availability = $request->query('availability')) {
            $query->where('availability', $availability);
        }

        // Búsqueda por especialidad (texto libre)
        if ($especialidad = $request->query('especialidad')) {
            $query->where('especialidad', 'like', "%{$especialidad}%");
        }

        // NUEVO: filtro por categoría nivel 2 exacta
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', (int) $categoryId);
        }

        // NUEVO: filtro por categoría nivel 1 (padre)
        // devuelve especialistas de cualquiera de sus categorías hijas
        if ($parentCategoryId = $request->query('parent_category_id')) {
            $query->whereHas('category', function ($q) use ($parentCategoryId) {
                $q->where('parent_id', (int) $parentCategoryId);
            });
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
                'last_page' => $specialists->lastPage(),
                'per_page' => $specialists->perPage(),
                'total' => $specialists->total(),
            ],
        ]);
    }

    // ── BY CATEGORY (nuevo endpoint público) ──────────────────────────────────

    /**
     * Devuelve los especialistas de una tienda filtrados por categoría nivel 2.
     *
     * GET /api/specialists/by-category/{categoryId}?store_id={storeId}
     *
     * Uso principal: cuando el vendedor está creando un servicio y ha
     * seleccionado la categoría nivel 2, este endpoint devuelve SOLO los
     * especialistas de esa categoría para asignarlos al servicio.
     *
     * Query params:
     *   - store_id   (requerido) → ID de la tienda
     *   - per_page   → paginación (default 100, sin límite razonable para un select)
     */
    public function byCategory(Request $request, int $categoryId): JsonResponse
    {
        $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $specialists = Specialist::query()
            ->where('store_id', (int) $request->query('store_id'))
            ->where('category_id', $categoryId)
            ->where('availability', 'Disponible')
            ->with('category.parent')
            ->orderBy('apellidos')
            ->paginate(min((int) $request->query('per_page', 100), 200));

        return response()->json([
            'data' => SpecialistResource::collection($specialists->items()),
            'meta' => [
                'current_page' => $specialists->currentPage(),
                'last_page' => $specialists->lastPage(),
                'per_page' => $specialists->perPage(),
                'total' => $specialists->total(),
            ],
        ]);
    }

    // ── STORE ─────────────────────────────────────────────────────────────────

    /**
     * Crea un nuevo especialista para la tienda del vendedor.
     *
     * POST /api/stores/me/specialists
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

        $specialist->load('category.parent');

        return response()->json(
            new SpecialistResource($specialist),
            201
        );
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────

    /**
     * Devuelve el detalle de un especialista.
     *
     * GET /api/stores/me/specialists/{specialist}
     */
    public function show(Request $request, int $specialist): JsonResponse
    {
        $model = Specialist::with(['schedules', 'category.parent'])->findOrFail($specialist);

        if (! $this->userOwnsSpecialist($request, $model)) {
            return $this->unauthorizedResponse();
        }

        return response()->json(new SpecialistResource($model));
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    /**
     * Actualiza los datos de un especialista.
     *
     * PUT /api/stores/me/specialists/{id}
     */
    public function update(UpdateSpecialistRequest $request, int $specialist): JsonResponse
    {
        $model = Specialist::findOrFail($specialist);

        if (! $this->userOwnsSpecialist($request, $model)) {
            return $this->unauthorizedResponse();
        }

        $model->update($request->validated());

        return response()->json(new SpecialistResource($model->fresh(['schedules', 'category.parent'])));
    }

    // ── DESTROY ───────────────────────────────────────────────────────────────

    /**
     * Elimina (soft delete) un especialista.
     *
     * DELETE /api/stores/me/specialists/{id}
     */
    public function destroy(Request $request, int $specialist): JsonResponse
    {
        $model = Specialist::findOrFail($specialist);

        if (! $this->userOwnsSpecialist($request, $model)) {
            return $this->unauthorizedResponse();
        }

        if ($model->hasFutureBookings()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar a este especialista porque tiene citas futuras pendientes o confirmadas. Cancélalas primero.',
            ], 422);
        }

        $model->delete();

        return response()->json(null, 204);
    }

    // ── HELPERS PRIVADOS ──────────────────────────────────────────────────────

    private function resolveStore(Request $request): mixed
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return $user->store
            ?? $user->stores()->where('status', 'approved')->first();
    }

    private function userOwnsSpecialist(Request $request, Specialist $specialist): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('administrator')) {
            return true;
        }

        return $user->ownedStores()->where('stores.id', $specialist->store_id)->exists()
            || $user->stores()->where('stores.id', $specialist->store_id)->exists();
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
