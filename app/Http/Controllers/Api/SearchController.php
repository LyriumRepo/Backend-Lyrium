<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    /**
     * GET /api/search
     * Unified search with optional ?type=all returning products + categories + total_hits.
     * Falls back to database search if Scout/Meilisearch is unavailable.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:all,products,categories'],
        ]);

        $queryStr = $request->query('q', '');
        $type = $request->query('type', 'products');

        try {
            $searchProducts = Product::search($queryStr)
                ->where('status', 'approved')
                ->take(20)
                ->get();

            $totalHits = $searchProducts->count();
        } catch (\Exception) {
            $searchProducts = $this->searchProductsDatabase($queryStr);
            $totalHits = $searchProducts->count();
        }

        $categories = collect();
        if ($type === 'all') {
            $categories = Category::where('name', 'like', "%{$queryStr}%")
                ->orWhere('description', 'like', "%{$queryStr}%")
                ->take(10)
                ->get();
        }

        return $this->success([
            'products' => ProductResource::collection($searchProducts),
            'categories' => $type === 'all' ? CategoryResource::collection($categories) : [],
            'total_hits' => $totalHits,
            'processing_time_ms' => 0,
        ]);
    }

    /**
     * GET /api/search/products
     * Search products with filters. Falls back to database search if Scout is unavailable.
     */
    public function products(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string'],
            'inStock' => ['nullable', 'string'],
            'minPrice' => ['nullable', 'numeric', 'min:0'],
            'maxPrice' => ['nullable', 'numeric', 'min:0'],
            'sticker' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Product::query()->where('status', 'approved');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $category));
        }

        if ($request->query('inStock') === 'true') {
            $query->where('stock', '>', 0);
        }

        if ($minPrice = $request->query('minPrice')) {
            $query->where('price', '>=', (float) $minPrice);
        }

        if ($maxPrice = $request->query('maxPrice')) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        if ($sticker = $request->query('sticker')) {
            $query->where('sticker', $sticker);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $products = $query->take($perPage)->get();

        return $this->success([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => 1,
                'per_page' => $perPage,
                'total' => $products->count(),
            ],
        ]);
    }

    /**
     * GET /api/search/suggestions
     * Get search suggestions/autocomplete. Falls back to database.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            $suggestions = Product::search($request->query('q'))
                ->take(10)
                ->get(['id', 'name', 'slug']);
        } catch (\Exception) {
            $suggestions = Product::query()
                ->where('status', 'approved')
                ->where('name', 'like', '%'.$request->query('q').'%')
                ->take(10)
                ->get(['id', 'name', 'slug']);
        }

        return $this->success([
            'data' => $suggestions->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ]),
        ]);
    }

    private function searchProductsDatabase(string $query): \Illuminate\Support\Collection
    {
        return Product::query()
            ->where('status', 'approved')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->take(20)
            ->get();
    }
}
