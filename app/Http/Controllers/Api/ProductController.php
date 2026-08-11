<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\ProductStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Notifications\ProductPendingReviewNotification;
use App\Notifications\ProductStatusNotification;
use App\Services\PlanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ProductController extends Controller
{
    // Relaciones base para listados
    private const RELATIONS_LIST = [
        'categories',
        'store',
        'mainAttributes',
        'additionalAttributes',
        'nutritionalAttributes',
        'media',
    ];

    // Relaciones completas para detalle/mutaciones
    private const RELATIONS_DETAIL = [
        'store',
        'categories',
        'mainAttributes',
        'additionalAttributes',
        'nutritionalAttributes',
        'media',
    ];

    /**
     * GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(self::RELATIONS_LIST)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');
        $user = $request->user() ?? $request->user('sanctum');

        if ($user?->hasRole('administrator')) {
            // Admin ve todo
        } elseif ($user?->store && $request->query('store_slug') === null && $request->query('store_id') === null) {
            // Vendedor viendo SUS propios productos (panel /seller/products) — ve todos los estados
            $query->where('store_id', $user->store->id);
        } else {
            // Cualquier otra solicitud pública (incluso vendedor viendo otra tienda o la propia vía slug) — solo aprobados
            $query->where('status', 'approved');
        }

        $this->applyFilters($query, $request);

        $perPage = min((int) $request->query('per_page', 15), 100);
        $products = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'total_pages' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/seller/products (auth required — seller's own products)
     */
    public function myProducts(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = $user?->store;

        if (! $store) {
            return response()->json(['success' => true, 'data' => [], 'meta' => [
                'current_page' => 1, 'per_page' => 100, 'total' => 0, 'total_pages' => 1,
            ]]);
        }

        $query = Product::with(self::RELATIONS_LIST)->where('store_id', $store->id);
        $this->applyFilters($query, $request);

        $perPage = min((int) $request->query('per_page', 15), 100);
        $products = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'total_pages' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/products
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Product::with(self::RELATIONS_LIST)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($cat) use ($search) {
                        $cat->where('name', 'like', "%{$search}%");
                    })
            );
        }

        if ($categorySlug = $request->query('category')) {
            $category = Category::where('slug', $categorySlug)->first();

            if ($category) {
                $category->load([
                    'children',
                    'children.children',
                ]);

                $ids = $this->getDescendantIds($category);

                $query->whereHas('categories', function ($q) use ($ids) {
                    $q->whereIn('categories.id', $ids);
                });
            }
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $products = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'total_pages' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/products/{slug}
     * Acepta slug o ID numérico
     */
    public function show(string $id): JsonResponse
    {
        $product = is_numeric($id)
            ? Product::with(self::RELATIONS_DETAIL)
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->findOrFail($id)
            : Product::with(self::RELATIONS_DETAIL)
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->where('slug', $id)->firstOrFail();

        return response()->json(new ProductResource($product));
    }

    /**
     * POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $store = $user->store;

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada.'], 403);
        }

        $type = $data['type'] ?? 'physical';

        if ($stickerError = $this->validateStickerAllowed($store, $data['sticker'] ?? null)) {
            return response()->json(['message' => $stickerError, 'upgrade_required' => true], 403);
        }

        $productData = [
            'store_id' => $store->id,
            'type' => $type,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(5),
            'description' => $data['description'] ?? '',
            'short_description' => $data['short_description'] ?? null,
            'price' => $data['price'],
            'stock' => $data['stock'] ?? 0,
            'image' => $data['image'] ?? null,
            'sticker' => $data['sticker'] ?? null,
            'discount_percentage' => $data['discountPercentage'] ?? null,
            'status' => 'pending_review',
        ];

        if ($type === 'physical') {
            $productData['weight'] = $data['weight'] ?? null;
            $productData['dimensions'] = $data['dimensions'] ?? null;
            $productData['expiration_date'] = $data['expirationDate'] ?? null;
        }

        if ($type === 'digital') {
            $productData['download_url'] = $data['downloadUrl'];
            $productData['download_limit'] = $data['downloadLimit'] ?? null;
            $productData['file_type'] = $data['fileType'] ?? null;
            $productData['file_size'] = $data['fileSize'] ?? null;
        }

        if ($type === 'service') {
            $productData['service_duration'] = $data['serviceDuration'];
            $productData['service_modality'] = $data['serviceModality'];
            $productData['service_location'] = $data['serviceLocation'] ?? null;
        }

        if (! empty($data['discountPercentage']) && $data['discountPercentage'] > 0 && $data['discountPercentage'] < 100) {
            $productData['regular_price'] = round(
                $data['price'] / (1 - $data['discountPercentage'] / 100), 2
            );
        }

        $product = Product::create($productData);

        $this->syncCategory($product, $data['category'] ?? null);
        $this->syncAttributes($product, $data);

        $product->load(self::RELATIONS_DETAIL);

        // Notificar a administradores: producto pendiente de revisión
        $admins = User::role('administrator')->get();
        $notification = new ProductPendingReviewNotification($product);
        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        return response()->json(new ProductResource($product), 201);
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        Gate::authorize('update', $product);

        $user = $request->user();
        $isAdministrator = $user->hasRole('administrator');
        $canManagePublishedProduct = in_array($product->status, ['approved', 'inactive'], true);

        if (! $isAdministrator && ! $canManagePublishedProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Este producto está esperando la aprobación del administrador.',
            ], 403);
        }

        $data = $request->validated();

        if (array_key_exists('sticker', $data)) {
            if ($stickerError = $this->validateStickerAllowed($product->store, $data['sticker'])) {
                return response()->json(['message' => $stickerError, 'upgrade_required' => true], 403);
            }
        }

        $updateData = $this->buildUpdateData($data, $product->type);

        $product->update($updateData);

        if (array_key_exists('category', $data)) {
            $this->syncCategory($product, $data['category']);
        }

        $this->syncAttributes($product, $data, update: true);

        $product->load(self::RELATIONS_DETAIL);

        return response()->json(new ProductResource($product));
    }

    /**
     * PUT /api/products/{id}/visibility
     */
    public function toggleVisibility(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        Gate::authorize('update', $product);

        $validated = $request->validate([
            'visible' => 'required|boolean',
        ]);

        if (! in_array($product->status, ['approved', 'inactive'], true)) {
            return response()->json([
                'message' => 'El producto debe estar aprobado para cambiar su visibilidad.',
            ], 403);
        }

        $product->update([
            'status' => $validated['visible'] ? 'approved' : 'inactive',
        ]);

        $product->load(self::RELATIONS_DETAIL);

        return response()->json(new ProductResource($product));
    }

    /**
     * DELETE /api/products/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->trashed()) {
            return response()->json(['success' => true, 'message' => 'Producto ya eliminado.']);
        }

        $product->delete();

        return response()->json(['success' => true]);
    }

    /**
     * PUT /api/products/{id}/stock
     */
    public function updateStock(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product->update(['stock' => $validated['stock_quantity']]);
        $product->load(self::RELATIONS_DETAIL);

        return response()->json(new ProductResource($product));
    }

    /**
     * PUT /api/products/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:approved,rejected,pending_review',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Si se rechaza, el reason es obligatorio
        if ($validated['status'] === 'rejected' && empty($validated['reason'])) {
            return response()->json([
                'message' => 'El motivo de rechazo es obligatorio.',
                'errors' => ['reason' => ['Debes indicar el motivo del rechazo.']],
            ], 422);
        }

        $product->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['status'] === 'rejected'
                ? $validated['reason']
                : null,                              // limpiar si se aprueba
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        $product->load(self::RELATIONS_DETAIL);

        try {
            broadcast(new ProductStatusChanged($product));
        } catch (\Throwable) {
            // Real-time broadcast unavailable; data was saved successfully
        }

        // Notificar al vendedor sobre cambio de estado
        $product->load('store.owner');
        $owner = $product->store?->owner;
        if ($owner) {
            try {
                $owner->notify(new ProductStatusNotification(
                    product: $product,
                    newStatus: $validated['status'],
                    reason: $validated['reason'] ?? null,
                ));
            } catch (\Throwable $e) {
                Log::error('[Product] Error notificando ProductStatus al vendedor', [
                    'product_id' => $product->id, 'owner_id' => $owner->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(new ProductResource($product));
    }

    // ─────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = $request->query('search')) {
            $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($cat) use ($search) {
                        $cat->where('name', 'like', "%{$search}%");
                    })
            );
        }

        if ($categorySlug = $request->query('category')) {

            $category = Category::where('slug', $categorySlug)->first();

            // Fallback: try last segment of compound slug path
            if (! $category && str_contains($categorySlug, '-')) {
                $parts = explode('-', $categorySlug);
                $lastSegment = end($parts);
                $category = Category::where('slug', $lastSegment)
                    ->orWhere('slug', 'like', "%-{$lastSegment}")
                    ->first();
            }

            if ($category) {

                $category->load([
                    'children',
                    'children.children',
                ]);

                $ids = $this->getDescendantIds($category);

                $query->whereHas('categories', function ($q) use ($ids) {
                    $q->whereIn('categories.id', $ids);
                });
            }
        }

        if ($categoryId = $request->query('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('id', $categoryId));
        }

        if ($request->boolean('on_sale')) {
            $query->where('discount_percentage', '>', 0);
        }
        if ($minPrice = $request->query('min_price')) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice = $request->query('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        if ($request->boolean('new')) {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        if ($sticker = $request->query('sticker')) {
            $query->where('sticker', $sticker);
        }

        if ($request->query('inStock') === 'true') {
            $query->where('stock', '>', 0);
        }

        if ($status = $request->query('status')) {
            $user = $request->user() ?? $request->user('sanctum');
            if ($user?->hasRole('administrator') || $user?->hasRole('seller') || $status === 'approved') {
                $query->where('status', $status);
            }
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($slug = $request->query('slug')) {
            $query->where('slug', $slug);
        }

        if ($storeSlug = $request->query('store_slug')) {
            $query->whereHas('store', fn ($q) => $q->where('slug', $storeSlug));
        }

        if ($storeId = $request->query('store_id')) {
            $query->where('store_id', (int) $storeId);
        }

        // Protección: status filter en applyFilters solo acepta 'approved' para no-admins
        // (el filtro de status en adminIndex es independiente y seguro)
    }

    private function syncCategory(Product $product, ?string $categorySlug): void
    {
        if (! $categorySlug) {
            return;
        }

        $category = Category::where('slug', $categorySlug)->first();

        if ($category) {
            $product->categories()->sync([$category->id]);
        }
    }

    /**
     * Crea o reemplaza atributos según el contexto (store vs update).
     * En update solo toca los grupos que vienen en el request.
     */
    private function syncAttributes(Product $product, array $data, bool $update = false): void
    {
        $groups = [
            'mainAttributes' => 'main',
            'additionalAttributes' => 'additional',
            'nutritionalAttributes' => 'nutritional',
        ];

        foreach ($groups as $key => $type) {
            if (! array_key_exists($key, $data)) {
                // En update: si no viene el grupo, no se toca
                if ($update) {
                    continue;
                }

                // En store: si no viene, tampoco se crea nada
                continue;
            }

            // Borrar los existentes de ese tipo
            $product->attributes()->where('type', $type)->delete();

            if (empty($data[$key])) {
                continue;
            }

            // El primer registro de nutritional puede ser el serving_note
            if ($type === 'nutritional' && ! empty($data['servingNote'])) {
                $product->attributes()->create([
                    'type' => 'nutritional',
                    'values' => ['serving_note' => $data['servingNote']],
                ]);
            }

            foreach ($data[$key] as $attr) {
                $values = $attr['values'] ?? [];

                // Normalizar: si el frontend envía [label, value, daily_value?], convertir a objeto
                if (is_array($values) && isset($values[0]) && ! isset($values['label'])) {
                    $normalized = [
                        'label' => $values[0] ?? '',
                        'value' => $values[1] ?? '',
                    ];
                    if ($type === 'nutritional' && ! empty($values[2])) {
                        $normalized['daily_value'] = $values[2];
                    }
                    $values = $normalized;
                }

                $product->attributes()->create([
                    'type' => $type,
                    'values' => $values,
                ]);
            }
        }
    }

    /**
     * Verifica que el sticker elegido esté permitido por el plan de la tienda.
     * Devuelve un mensaje de error si no lo está, o null si es válido.
     */
    private function validateStickerAllowed(Store $store, ?string $sticker): ?string
    {
        if (! $sticker) {
            return null;
        }

        $allowed = app(PlanService::class)->storeCapabilities($store)['sticker_types'] ?? [];

        if (! in_array($sticker, $allowed, true)) {
            return "Tu plan actual no permite el sticker '{$sticker}'. Actualiza tu plan para usar todos los stickers.";
        }

        return null;
    }

    /**
     * Construye el array de campos a actualizar de forma segura.
     */
    private function buildUpdateData(array $data, string $type): array
    {
        $map = [
            'name' => 'name',
            'description' => 'description',
            'short_description' => 'short_description',
            'price' => 'price',
            'stock' => 'stock',
            'image' => 'image',
            'sticker' => 'sticker',
            'discountPercentage' => 'discount_percentage',
        ];

        $typeMap = [
            'physical' => [
                'weight' => 'weight',
                'dimensions' => 'dimensions',
                'expirationDate' => 'expiration_date',
            ],
            'digital' => [
                'downloadUrl' => 'download_url',
                'downloadLimit' => 'download_limit',
                'fileType' => 'file_type',
                'fileSize' => 'file_size',
            ],
            'service' => [
                'serviceDuration' => 'service_duration',
                'serviceModality' => 'service_modality',
                'serviceLocation' => 'service_location',
            ],
        ];

        $updateData = [];

        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $updateData[$column] = $data[$input];
            }
        }

        foreach ($typeMap[$type] ?? [] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $updateData[$column] = $data[$input];
            }
        }

        if (array_key_exists('discountPercentage', $data) && array_key_exists('price', $data)) {
            if ($data['discountPercentage'] > 0 && $data['discountPercentage'] < 100) {
                $updateData['regular_price'] = round(
                    $data['price'] / (1 - $data['discountPercentage'] / 100), 2
                );
            } else {
                $updateData['regular_price'] = $data['price'];
            }
        }

        return $updateData;
    }

    // Función para obtener categorias decendientes.
    private function getDescendantIds(Category $category): array
    {
        $ids = [$category->id];

        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}
