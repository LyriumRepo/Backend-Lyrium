<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\StoreStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateRequest;
use App\Http\Resources\StoreResource;
use App\Models\Contract;
use App\Models\Store;
use App\Notifications\StoreStatusNotification;
use App\Services\ContractDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class StoreController extends Controller
{
    /**
     * GET /api/stores/me
     * Retorna la tienda del vendedor autenticado
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $store = Store::with(['category', 'subscription.plan', 'branches'])
            ->where('owner_id', $user->id)
            ->first();

        if (! $store) {
            return response()->json([
                'data' => null,
                'message' => 'No tienes una tienda registrada',
            ], 404);
        }

        return response()->json([
            'data' => new StoreResource($store),
        ]);
    }

    /**
     * GET /api/stores (listado público con filtros)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Store::with(['owner', 'category', 'contracts' => fn ($q) => $q->latest()]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trade_name', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%")
                    ->orWhere('corporate_email', 'like', "%{$search}%")
                    ->orWhere('razon_social', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $stores = $query->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 50));

        // Serializar la colección explícitamente para evitar el doble-wrapping que
        // produce json_encode sobre un AnonymousResourceCollection paginado.
        return response()->json([
            'data' => StoreResource::collection($stores)->resolve(),
            'pagination' => [
                'page' => $stores->currentPage(),
                'perPage' => $stores->perPage(),
                'total' => $stores->total(),
                'totalPages' => $stores->lastPage(),
                'hasMore' => $stores->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/stores/{id}
     */
    public function show(int $id): JsonResponse
    {
        $store = Store::with(['owner', 'subscription.plan', 'category'])->findOrFail($id);

        return response()->json(new StoreResource($store));
    }

    /**
     * POST /api/stores
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'trade_name' => 'required|string|max:255',
            'ruc' => 'required|string|size:11|unique:stores,ruc',
            'corporate_email' => 'required|email',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'rep_legal_nombre' => 'nullable|string|max:255',
            'rep_legal_dni' => 'nullable|string|max:20',
            'experience_years' => 'nullable|integer|min:0|max:100',
            'tax_condition' => 'nullable|string|max:100',
            'direccion_fiscal' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'address' => 'nullable|string',
        ]);

        $data['owner_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['trade_name']);

        $store = Store::create($data);

        return response()->json(new StoreResource($store), 201);
    }

    /**
     * PUT /api/stores/{id}
     */
    public function update(StoreUpdateRequest $request, int $id): JsonResponse
    {
        $store = Store::findOrFail($id);

        $data = $request->validated();

        if (isset($data['bank_secondary'])) {
            $data['bank_secondary'] = json_encode($data['bank_secondary']);
        }

        $store->update($data);

        return response()->json(new StoreResource($store->fresh()->load(['owner', 'category'])));
    }

    /**
     * PUT /api/stores/{id}/status
     * Admin: aprobar, rechazar o banear vendedores
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $store = Store::with('owner')->findOrFail($id);

        $data = $request->validate([
            'status' => 'required|string|in:approved,rejected,banned',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($data['status'] === 'approved' && ! $store->isProfileComplete()) {
            return response()->json([
                'message' => 'No se puede aprobar la tienda: el perfil esta incompleto.',
                'missing_fields' => $store->missingProfileFields(),
            ], 422);
        }

        $updateData = ['status' => $data['status']];

        if ($data['status'] === 'approved') {
            $updateData['approved_at'] = now();
        }

        if ($data['status'] === 'banned') {
            $updateData['banned_at'] = now();
        }

        $store->update($updateData);

        // Generar contrato automáticamente al aprobar
        if ($data['status'] === 'approved') {
            $this->generateContractForStore($store->fresh());
        }

        // Enviar notificación al propietario de la tienda
        $store->owner->notify(new StoreStatusNotification(
            $store,
            $data['status'],
            $data['reason'] ?? null,
        ));

        broadcast(new StoreStatusChanged($store->fresh()));

        return response()->json(new StoreResource($store->fresh()->load(['owner', 'category'])));
    }

    /**
     * GET /api/stores/{id}/branches
     * Listar sucursales de una tienda
     */
    public function branches(int $id): JsonResponse
    {
        $store = Store::findOrFail($id);
        $branches = $store->branches()->where('is_active', true)->get();

        return response()->json([
            'data' => $branches->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'city' => $branch->city,
                'phone' => $branch->phone,
                'hours' => $branch->hours,
                'is_principal' => $branch->is_principal,
                'maps_url' => $branch->maps_url,
            ]),
        ]);
    }

    /**
     * PUT /api/stores/{id}/branches
     * Actualizar todas las sucursales (sync)
     */
    public function updateBranches(Request $request, int $id): JsonResponse
    {
        $store = Store::findOrFail($id);

        $data = $request->validate([
            'branches' => 'required|array',
            'branches.*.id' => 'nullable|integer',
            'branches.*.name' => 'required|string|max:255',
            'branches.*.address' => 'required|string|max:500',
            'branches.*.city' => 'required|string|max:255',
            'branches.*.phone' => 'required|string|max:20',
            'branches.*.hours' => 'nullable|string|max:100',
            'branches.*.is_principal' => 'boolean',
            'branches.*.maps_url' => 'nullable|string|max:500',
        ]);

        $existingIds = $store->branches()->pluck('id')->toArray();
        $incomingIds = collect($data['branches'])->pluck('id')->filter()->toArray();

        $toDelete = array_diff($existingIds, $incomingIds);
        if (! empty($toDelete)) {
            $store->branches()->whereIn('id', $toDelete)->delete();
        }

        foreach ($data['branches'] as $branchData) {
            $branchData['store_id'] = $store->id;
            unset($branchData['id']);

            $store->branches()->updateOrCreate(
                ['id' => $branchData['id'] ?? null],
                $branchData
            );
        }

        return response()->json(new StoreResource($store->fresh()->load(['owner', 'category', 'branches'])));
    }

    /**
     * PUT /api/stores/me/visual
     * Actualizar layout + identidad visual (URLs)
     */
    public function updateVisual(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $data = $request->validate([
            'layout' => 'required|in:1,2,3',
            'logo' => 'nullable|url',
            'banner' => 'nullable|url',
            'banner_secondary' => 'nullable|url',
            'gallery' => 'nullable|array',
            'gallery.*' => 'url',
        ]);

        $store->update([
            'layout' => $data['layout'],
            'logo' => $data['logo'] ?? $store->logo,
            'banner' => $data['banner'] ?? $store->banner,
            'banner2' => $data['banner_secondary'] ?? $store->banner2,
            'gallery' => $data['gallery'] ?? $store->gallery,
        ]);

        return response()->json(new StoreResource($store->fresh()));
    }

    /**
     * POST /api/stores/me/media/logo
     * Upload de logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $store->clearMediaCollection('logo');
        $media = $store->addMediaFromRequest('file')->toMediaCollection('logo');

        $store->update(['logo' => $media->getUrl()]);

        return response()->json([
            'url' => $media->getUrl(),
            'message' => 'Logo actualizado correctamente',
        ]);
    }

    /**
     * POST /api/stores/me/media/banner
     * Upload de banner(s)
     */
    public function uploadBanner(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'type' => 'nullable|in:banner,banner2',
        ]);

        $type = $request->input('type', 'banner');
        $collection = $type === 'banner2' ? 'banner2' : 'banner';

        $store->clearMediaCollection($collection);
        $media = $store->addMediaFromRequest('file')->toMediaCollection($collection);

        $column = $collection === 'banner2' ? 'banner2' : 'banner';
        $store->update([$column => $media->getUrl()]);

        return response()->json([
            'url' => $media->getUrl(),
            'type' => $type,
            'message' => 'Banner actualizado correctamente',
        ]);
    }

    /**
     * POST /api/stores/me/media/gallery
     * Upload de imágenes a galería
     */
    public function uploadGallery(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $request->validate([
            'files.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $urls = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $media = $store->addMedia($file)->toMediaCollection('gallery');
                $urls[] = $media->getUrl();
            }
        }

        $currentGallery = $store->gallery ?? [];
        $store->update(['gallery' => array_merge($currentGallery, $urls)]);

        return response()->json([
            'urls' => $urls,
            'gallery' => $store->fresh()->gallery,
            'message' => count($urls).' imágenes agregadas a la galería',
        ]);
    }

    /**
     * Genera y persiste el contrato digital al aprobar una tienda.
     */
    private function generateContractForStore(Store $store): void
    {
        // Evitar duplicados: solo generar si no existe ya un contrato para esta tienda
        if ($store->contracts()->exists()) {
            return;
        }

        $contractNumber = ContractDocumentService::generateContractNumber();

        $service  = new ContractDocumentService();
        $filePath = $service->generate($store, $contractNumber);

        $contract = Contract::create([
            'contract_number' => $contractNumber,
            'store_id'        => $store->id,
            'company'         => $store->razon_social ?? $store->trade_name,
            'ruc'             => $store->ruc,
            'representative'  => $store->rep_legal_nombre,
            'type'            => 'Convenio Digital',
            'modality'        => 'Digital',
            'status'          => 'PENDING',
            'start_date'      => now()->toDateString(),
            'end_date'        => null,
            'file_path'       => $filePath,
        ]);

        $contract->addAuditEntry(
            'Contrato generado automáticamente por aprobación de tienda',
            'Sistema'
        );
    }

    /**
     * DELETE /api/stores/me/media/gallery/{index}
     * Eliminar imagen de galería por índice
     */
    public function deleteGalleryImage(Request $request, int $index): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $gallery = $store->gallery ?? [];

        if (! isset($gallery[$index])) {
            return response()->json(['message' => 'Imagen no encontrada'], 404);
        }

        $media = $store->media()->where('collection_name', 'gallery')->get()[$index] ?? null;
        $media?->delete();

        array_splice($gallery, $index, 1);
        $store->update(['gallery' => array_values($gallery)]);

        return response()->json([
            'gallery' => $store->fresh()->gallery,
            'message' => 'Imagen eliminada correctamente',
        ]);
    }
}
