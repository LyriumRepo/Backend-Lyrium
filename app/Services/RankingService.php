<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

final class RankingService
{
    public function getTopProducts(int $limit = 100, int $minReviews = 1): Collection
    {
        return Product::with(['store:id,store_name,slug,logo', 'categories:id,name,slug', 'media'])
            ->where('status', 'approved')
            ->where('rating_count', '>=', $minReviews)
            ->orderByDesc('rating_promedio')
            ->orderByDesc('rating_count')
            ->limit($limit)
            ->get();
    }

    public function getTopStores(int $limit = 20): Collection
    {
        return Store::withCount('reviews as review_count')
            ->withAvg('reviews as average_rating', 'rating')
            ->having('review_count', '>=', 1)
            ->orderByDesc('average_rating')
            ->orderByDesc('review_count')
            ->limit($limit)
            ->get();
    }

    public function getTopServices(int $limit = 100): Collection
    {
        return Service::with('store:id,store_name,slug,logo')
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
    }
}
