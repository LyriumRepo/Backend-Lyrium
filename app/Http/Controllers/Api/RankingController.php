<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductRankingResource;
use App\Http\Resources\ServiceRankingResource;
use App\Http\Resources\StoreRankingResource;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RankingController extends Controller
{
    /**
     * GET /api/rankings/products?limit=100&min_reviews=1
     * Público — top productos por rating
     */
    public function products(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 100), 100);
        $minReviews = max((int) $request->query('min_reviews', 1), 1);

        $products = Product::with(['store:id,store_name,slug,logo', 'categories:id,name,slug', 'media'])
            ->where('status', 'approved')
            ->where('rating_count', '>=', $minReviews)
            ->orderByDesc('rating_promedio')
            ->orderByDesc('rating_count')  // desempate por más reseñas
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductRankingResource::collection($products),
            'meta' => ['total' => $products->count(), 'min_reviews' => $minReviews],
        ]);
    }

    /**
     * GET /api/rankings/stores?limit=20
     * Público — top tiendas por rating promedio
     */
    public function stores(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 50);

        $stores = Store::withCount('storeReviews as store_review_count')
            ->withAvg('storeReviews as store_rating_average', 'rating')
            ->having('store_review_count', '>=', 1)
            ->orderByDesc('store_rating_average')
            ->orderByDesc('store_review_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => StoreRankingResource::collection($stores),
        ]);
    }

    /**
     * GET /api/rankings/services?limit=20
     * Público — top servicios por rating promedio
     */
    public function services(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 100);

        $services = Service::with('store:id,store_name,slug,logo')
            ->where('status', Service::STATUS_ACTIVE)
            ->select('services.*')
            ->selectSub(
                'SELECT COALESCE(AVG(r.rating), 0) FROM service_bookings sb '
                .'INNER JOIN reviews r ON r.service_booking_id = sb.id '
                .'WHERE sb.service_id = services.id',
                'rating_average',
            )
            ->selectSub(
                'SELECT COUNT(r.id) FROM service_bookings sb '
                .'INNER JOIN reviews r ON r.service_booking_id = sb.id '
                .'WHERE sb.service_id = services.id',
                'rating_count',
            )
            ->havingRaw('rating_count >= 1')
            ->orderByDesc('rating_average')
            ->orderByDesc('rating_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ServiceRankingResource::collection($services),
        ]);
    }
}
