<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductRankingResource;
use App\Http\Resources\ServiceRankingResource;
use App\Http\Resources\StoreRankingResource;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RankingController extends Controller
{
    public function __construct(
        private readonly RankingService $rankingService,
    ) {}

    public function products(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 100), 100);
        $minReviews = max((int) $request->query('min_reviews', 1), 1);

        $products = $this->rankingService->getTopProducts($limit, $minReviews);

        return response()->json([
            'success' => true,
            'data' => ProductRankingResource::collection($products),
            'meta' => ['total' => $products->count(), 'min_reviews' => $minReviews],
        ]);
    }

    public function stores(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 50);

        $stores = $this->rankingService->getTopStores($limit);

        return response()->json([
            'success' => true,
            'data' => StoreRankingResource::collection($stores),
        ]);
    }

    public function services(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 100);

        $services = $this->rankingService->getTopServices($limit);

        return response()->json([
            'success' => true,
            'data' => ServiceRankingResource::collection($services),
        ]);
    }
}
