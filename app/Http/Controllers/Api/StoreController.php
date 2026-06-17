<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;

use App\Events\StoreStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateRequest;
use App\Http\Resources\StoreResource;
use App\Models\Contract;
use App\Models\Store;
use App\Models\User;
use App\Notifications\StoreProfileUpdatedNotification;
use App\Notifications\StoreStatusNotification;
use App\Services\ContractDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class StoreController extends Controller
{
    /**
     * GET /api/stores (público)
     * Lista tiendas aprobadas con info básica
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = Store::with('category')
            ->where('status', 'approved');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trade_name', 'like', "%{$search}%")
                    ->orWhere('nombre_comercial', 'like', "%{$search}%")
                    ->orWhere('razon_social', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($city = $request->query('city')) {
            $query->where('address', 'like', "%{$city}%");
        }

        $stores = $query->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 12), 50));

        return response()->json([
            'success' => true,
            'data' => $stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->store_name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo' => $store->getMediaUrl('logo'),
                'banner' => $store->getMediaUrl('banner'),
                'address' => $store->address,
                'phone' => $store->phone,
                'category' => $store->category?->name,
                'rating' => (float) $store->rating,
                'product_count' => $store->products()->where('status', 'approved')->count(),
            ]),
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
     * GET /api/stores/{slug} (público)
     * Retorna detalle completo de una tienda por slug
     */
    public function publicShow(string $slug): JsonResponse
    {
        $store = Store::with(['category', 'branches' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->where('status', 'approved')
            ->first();

        if (! $store) {
            return response()->json(['success' => false, 'message' => 'Tienda no encontrada'], 404);
        }

        $plan = 'basico';
        if ($store->relationLoaded('subscription') && $store->subscription) {
            $plan = $store->subscription->plan?->name === 'Premium' ? 'premium' : 'basico';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $store->id,
                'name' => $store->store_name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo' => $store->getMediaUrl('logo'),
                'banner' => $store->getMediaUrl('banner'),
                'banner2' => $store->getMediaUrl('banner2'),
                'gallery' => $store->getGalleryUrls(),
                'address' => $store->address,
                'phone' => $store->phone,
                'email' => $store->corporate_email,
                'category' => $store->category?->name,
                'category_id' => $store->category_id,
                'rating' => (float) $store->rating,
                'layout' => $store->layout ?? '1',
                'plan' => $plan,
                'open' => true,
                'social' => [
                    'instagram' => $store->instagram,
                    'facebook' => $store->facebook,
                    'tiktok' => $store->tiktok,
                    'whatsapp' => $store->whatsapp,
                    'youtube' => $store->youtube,
                    'twitter' => $store->twitter,
                    'linkedin' => $store->linkedin,
                    'website' => $store->website,
                ],
                'branches' => $store->branches->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'address' => $b->address,
                    'city' => $b->city,
                    'phone' => $b->phone,
                    'hours' => $b->hours,
                    'is_principal' => $b->is_principal,
                    'maps_url' => $b->maps_url,
                ]),
                'stats' => [
                    'products' => $store->products()->where('status', 'approved')->count(),
                    'rating' => (float) $store->rating,
                    'reviews' => 0,
                ],
            ],
        ]);
    }

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
     * GET /api/stores/slug/{slug} — public endpoint for store page
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $store = Store::with(['subscription.plan', 'category', 'branches'])
            ->where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();

        return response()->json(['data' => new StoreResource($store)]);
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

        $this->notifyAdminStoreChanged($store, 'general');

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
                'department' => $branch->departmet,
                'province' => $branch->province,
                'district' => $branch->district,
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
            'branches.*.department' => 'required|string|max:255',
            'branches.*.province' => 'required|string|max:255',
            'branches.*.district' => 'required|string|max:255',
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

            $branchId = $branchData['id'] ?? null;

            unset($branchData['id']);

            $branchData['store_id'] = $store->id;

            $store->branches()->updateOrCreate(
                [
                    'id' => $branchId,
                    'store_id' => $store->id
                ],
                $branchData
            );
        }

        $this->notifyAdminStoreChanged($store, 'branches');

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

        $this->notifyAdminStoreChanged($store, 'visual');

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

        $this->notifyAdminStoreChanged($store, 'logo');

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

        $this->notifyAdminStoreChanged($store, 'banner');

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

        $this->notifyAdminStoreChanged($store, 'gallery');

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

        $service = new ContractDocumentService;
        $filePath = $service->generate($store, $contractNumber);

        $contract = Contract::create([
            'contract_number' => $contractNumber,
            'store_id' => $store->id,
            'company' => $store->razon_social ?? $store->trade_name,
            'ruc' => $store->ruc,
            'representative' => $store->rep_legal_nombre,
            'type' => 'Convenio Digital',
            'modality' => 'Digital',
            'status' => 'PENDING',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'file_path' => $filePath,
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

        $this->notifyAdminStoreChanged($store, 'gallery');

        return response()->json([
            'gallery' => $store->fresh()->gallery,
            'message' => 'Imagen eliminada correctamente',
        ]);
    }
    //UploadRepLegalPhoto
    public function uploadRepLegalPhoto(Request $request, int $id)
    {
        $request->validate([
            'file' => ['required', 'image']
        ]);

        $store = Store::findOrFail($id);

        $path = $request->file('file')
            ->store('stores', 'public');

        $url = Storage::url($path);

        $store->update([
            'rep_legal_foto' => $url
        ]);

        $this->notifyAdminStoreChanged($store, 'general');

        return response()->json([
            'url' => $url
        ]);
    }

    /**
     * Notifica a todos los administradores sobre cambios en la tienda.
     * Usa cache para evitar spam: máximo 1 notificación por tienda cada 30 minutos.
     */
    private function notifyAdminStoreChanged(Store $store, string $changeType = 'general'): void
    {
        $cacheKey = "store_profile_updated_notif_{$store->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(30));

        $admins = User::role('administrator')->get();
        foreach ($admins as $admin) {
            $admin->notify(new StoreProfileUpdatedNotification($store, $changeType));
        }
    }
}
