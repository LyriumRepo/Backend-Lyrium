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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ProductController extends Controller
{
    /**
     * GET /api/products
     * GET /api/products?on_sale=true&per_page=12
     * GET /api/products?new=true&per_page=8
     * GET /api/products?category_id=1&per_page=20
     * GET /api/products?slug=producto-slug
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['categories', 'store', 'mainAttributes', 'additionalAttributes', 'media']);

        $user = $request->user();

        // If user has a store, show all products from that store (including pending)
        if ($user && $user->store) {
            $query->where('store_id', $user->store->id);
        } elseif (! $user || ! $user->hasRole('administrator')) {
            // Public access - only approved products
            $query->where('status', 'approved');
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $category));
        }

        if ($categoryId = $request->query('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('id', $categoryId));
        }

        if ($request->boolean('on_sale')) {
            $query->where('discount_percentage', '>', 0);
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
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($slug = $request->query('slug')) {
            $query->where('slug', $slug);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = ProductResource::collection($products);

        return response()->json([
            'success' => true,
            'data' => $data,
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
     * Admin: listar todos los productos incluyendo pendientes
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Product::with(['categories', 'store', 'mainAttributes', 'additionalAttributes', 'media']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = ProductResource::collection($products);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'total_pages' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['categories', 'store', 'mainAttributes', 'additionalAttributes'])
            ->findOrFail($id);

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

        $productData = [
            'store_id' => $store->id,
            'type' => $type,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(5),
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'stock' => $data['stock'] ?? 0,
            'image' => $data['image'] ?? null,
            'discount_percentage' => $data['discountPercentage'] ?? null,
            'status' => 'pending_review',
        ];

        // Physical fields
        if ($type === 'physical') {
            $productData['weight'] = $data['weight'] ?? null;
            $productData['dimensions'] = $data['dimensions'] ?? null;
            $productData['expiration_date'] = $data['expirationDate'] ?? null;
        }

        // Digital fields
        if ($type === 'digital') {
            $productData['download_url'] = $data['downloadUrl'];
            $productData['download_limit'] = $data['downloadLimit'] ?? null;
            $productData['file_type'] = $data['fileType'] ?? null;
            $productData['file_size'] = $data['fileSize'] ?? null;
        }

        // Service fields
        if ($type === 'service') {
            $productData['service_duration'] = $data['serviceDuration'];
            $productData['service_modality'] = $data['serviceModality'];
            $productData['service_location'] = $data['serviceLocation'] ?? null;
        }

        $product = Product::create($productData);

        // Asociar categoría
        if (! empty($data['category'])) {
            $category = Category::where('slug', $data['category'])->first();
            if ($category) {
                $product->categories()->attach($category->id);
            }
        }

        // Crear atributos
        if (! empty($data['mainAttributes'])) {
            foreach ($data['mainAttributes'] as $attr) {
                $product->attributes()->create([
                    'type' => 'main',
                    'values' => $attr['values'] ?? [],
                ]);
            }
        }

        if (! empty($data['additionalAttributes'])) {
            foreach ($data['additionalAttributes'] as $attr) {
                $product->attributes()->create([
                    'type' => 'additional',
                    'values' => $attr['values'] ?? [],
                ]);
            }
        }

        $product->load(['categories', 'mainAttributes', 'additionalAttributes']);

        return response()->json(new ProductResource($product), 201);
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();

        \Log::info('Update product data:', $data);

        $updateData = [];
        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (array_key_exists('price', $data)) {
            $updateData['price'] = $data['price'];
        }
        if (array_key_exists('stock', $data)) {
            $updateData['stock'] = $data['stock'];
        }
        if (array_key_exists('image', $data)) {
            $updateData['image'] = $data['image'];
        }
        if (array_key_exists('sticker', $data)) {
            $updateData['sticker'] = $data['sticker'];
        }
        if (array_key_exists('discountPercentage', $data)) {
            $updateData['discount_percentage'] = $data['discountPercentage'];
        }

        $type = $product->type;

        // Physical fields
        if ($type === 'physical') {
            if (array_key_exists('weight', $data)) {
                $updateData['weight'] = $data['weight'];
            }
            if (array_key_exists('dimensions', $data)) {
                $updateData['dimensions'] = $data['dimensions'];
            }
            if (array_key_exists('expirationDate', $data)) {
                $updateData['expiration_date'] = $data['expirationDate'];
            }
        }

        // Digital fields
        if ($type === 'digital') {
            if (isset($data['downloadUrl'])) {
                $updateData['download_url'] = $data['downloadUrl'];
            }
            if (array_key_exists('downloadLimit', $data)) {
                $updateData['download_limit'] = $data['downloadLimit'];
            }
            if (array_key_exists('fileType', $data)) {
                $updateData['file_type'] = $data['fileType'];
            }
            if (array_key_exists('fileSize', $data)) {
                $updateData['file_size'] = $data['fileSize'];
            }
        }

        // Service fields
        if ($type === 'service') {
            if (isset($data['serviceDuration'])) {
                $updateData['service_duration'] = $data['serviceDuration'];
            }
            if (isset($data['serviceModality'])) {
                $updateData['service_modality'] = $data['serviceModality'];
            }
            if (array_key_exists('serviceLocation', $data)) {
                $updateData['service_location'] = $data['serviceLocation'];
            }
        }

        $product->update($updateData);

        // Actualizar categoría
        if (array_key_exists('category', $data)) {
            $category = Category::where('slug', $data['category'])->first();
            if ($category) {
                $product->categories()->sync([$category->id]);
            }
        }

        // Actualizar atributos (solo si se envía explícitamente)
        if (array_key_exists('mainAttributes', $data)) {
            $product->mainAttributes()->delete();
            if (! empty($data['mainAttributes'])) {
                foreach ($data['mainAttributes'] as $attr) {
                    $product->attributes()->create([
                        'type' => 'main',
                        'values' => $attr['values'] ?? [],
                    ]);
                }
            }
        }

        if (array_key_exists('additionalAttributes', $data)) {
            $product->additionalAttributes()->delete();
            if (! empty($data['additionalAttributes'])) {
                foreach ($data['additionalAttributes'] as $attr) {
                    $product->attributes()->create([
                        'type' => 'additional',
                        'values' => $attr['values'] ?? [],
                    ]);
                }
            }
        }

        $product->load(['categories', 'mainAttributes', 'additionalAttributes']);

        return response()->json(new ProductResource($product));
    }

    /**
     * DELETE /api/products/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->trashed()) {
            return response()->json(['success' => true, 'message' => 'Producto ya eliminado']);
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

        $data = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $product->update(['stock' => $data['quantity']]);
        $product->load(['categories', 'mainAttributes', 'additionalAttributes']);

        return response()->json(new ProductResource($product));
    }

    /**
     * PUT /api/products/{id}/status
     * Admin: aprobar o rechazar productos
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|string|in:approved,rejected,pending_review',
            'reason' => 'nullable|string',
        ]);

        $product->update(['status' => $data['status']]);
        $product->load(['categories', 'mainAttributes', 'additionalAttributes']);

        broadcast(new ProductStatusChanged($product));

        return response()->json(new ProductResource($product));
    }
}
