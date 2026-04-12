<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\CategoryUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CategoryController extends Controller
{
    /**
     * GET /api/categories
     * GET /api/categories?type=product|service
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount('products');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->boolean('hide_empty')) {
            $query->has('products');
        }

        if ($request->boolean('tree')) {
            $query->whereNull('parent_id')->with('children.children');
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if (! $request->query('type') && ! $request->query('tree') && ! $request->query('search')) {
            $query->where(function ($q) {
                $q->where('type', 'product')->orWhereNull('type');
            });
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $categories = $query->orderBy('sort_order')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'total_pages' => $categories->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/categories/mega-menu
     * Devuelve categorías en formato árbol con 3 niveles para el mega-menú público.
     */
    public function megaMenu(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->orderBy('sort_order')
                  ->with(['children' => function ($q2) {
                      $q2->orderBy('sort_order');
                  }]);
            }])
            ->orderBy('type')->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(function ($cat) {
                $prefix = $cat->type === 'service' ? '/servicios' : '/productos';
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'type' => $cat->type ?? 'product',
                    'image' => $cat->image ? asset($cat->image) : null,
                    'children' => $cat->children->map(function ($sub) use ($cat, $prefix) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'slug' => $sub->slug,
                            'image' => $sub->image ? asset($sub->image) : null,
                            'href' => $prefix . '/' . $cat->slug . '/' . $sub->slug,
                            'children' => $sub->children->map(function ($subsub) use ($cat, $prefix) {
                                return [
                                    'id' => $subsub->id,
                                    'name' => $subsub->name,
                                    'slug' => $subsub->slug,
                                    'href' => $prefix . '/' . $cat->slug . '/' . $subsub->slug,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * GET /api/categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::withCount('products')
            ->with('children.children')
            ->findOrFail($id);

        return response()->json(new CategoryResource($category));
    }

    /**
     * POST /api/categories
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent' => 'nullable|integer|exists:categories,id',
            'image' => 'nullable|string',
            'type' => 'nullable|string|in:product,service',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Validar profundidad máxima (3 niveles)
        if (isset($data['parent'])) {
            $parent = Category::find($data['parent']);
            if ($parent && $parent->parent_id) {
                $grandparent = Category::find($parent->parent_id);
                if ($grandparent && $grandparent->parent_id !== null) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No se pueden crear categorías de más de 3 niveles de profundidad.',
                    ], 422);
                }
            }
        }

        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent'] ?? null,
            'image' => $data['image'] ?? null,
            'type' => $data['type'] ?? 'product',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        broadcast(new CategoryUpdated($category->loadCount('products'), 'created'));

        return response()->json(new CategoryResource($category->loadCount('products')), 201);
    }

    /**
     * PUT /api/categories/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent' => 'nullable|integer|exists:categories,id',
            'image' => 'nullable|string',
            'type' => 'nullable|string|in:product,service',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = Str::slug($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (array_key_exists('parent', $data)) {
            $updateData['parent_id'] = $data['parent'];
        }
        if (array_key_exists('image', $data)) {
            $updateData['image'] = $data['image'];
        }
        if (array_key_exists('type', $data)) {
            $updateData['type'] = $data['type'];
        }
        if (array_key_exists('sort_order', $data)) {
            $updateData['sort_order'] = $data['sort_order'];
        }

        $category->update($updateData);

        broadcast(new CategoryUpdated($category->fresh()->loadCount('products'), 'updated'));

        return response()->json(new CategoryResource($category->fresh()->loadCount('products')));
    }

    /**
     * POST /api/categories/{id}/image
     */
    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:webp,png,jpg,jpeg|max:2048',
        ]);

        $path = $request->file('image')->store('img/categorias', 'public');
        $relativePath = '/storage/' . $path;

        $category->update(['image' => $relativePath]);

        return response()->json([
            'success' => true,
            'image' => asset($relativePath),
        ]);
    }

    /**
     * DELETE /api/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->products()->detach();
        $category->delete();

        broadcast(new CategoryUpdated($category, 'deleted'));

        return response()->json(new CategoryResource($category));
    }
}
