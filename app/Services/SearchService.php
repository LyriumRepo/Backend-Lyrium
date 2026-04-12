<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SearchService
{
    public function searchProducts(Request $request): array
    {
        $query = $request->query('q', '');
        $category = $request->query('category');
        $inStock = $request->query('inStock', 'false') === 'true';
        $minPrice = $request->query('minPrice');
        $maxPrice = $request->query('maxPrice');
        $sticker = $request->query('sticker');
        $perPage = min((int) $request->query('per_page', 15), 100);

        $products = Product::search($query)
            ->where('status', 'approved')
            ->when($category, fn ($q) => $q->where('categories', $category))
            ->when($inStock, fn ($q) => $q->where('stock', '>', 0))
            ->when($minPrice, fn ($q) => $q->where('price', '>=', (float) $minPrice))
            ->when($maxPrice, fn ($q) => $q->where('price', '<=', (float) $maxPrice))
            ->when($sticker, fn ($q) => $q->where('sticker', $sticker))
            ->with(['categories', 'mainAttributes', 'additionalAttributes'])
            ->paginate($perPage);

        return [
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ];
    }

    public function searchCategories(Request $request): Collection
    {
        $query = $request->query('q', '');

        return Product::search($query)
            ->select(['categories'])
            ->take(100)
            ->get()
            ->pluck('categories')
            ->flatten()
            ->unique()
            ->values();
    }
}
