<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

final class StoreReview extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'order_id',
        'rating',
        'rating_communication',
        'rating_shipping',
        'rating_packaging',
        'title',
        'comment',
        'is_verified_purchase',
        'reported_count',
    ];

    protected function casts(): array
    {
        return [
            'rating'               => 'integer',
            'rating_communication' => 'integer',
            'rating_shipping'      => 'integer',
            'rating_packaging'     => 'integer',
            'is_verified_purchase' => 'boolean',
            'reported_count'       => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Stats completos en una sola query
     */
    public static function getStoreStats(int $storeId): array
    {
        $row = DB::table('store_reviews')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND(AVG(rating), 1) as average'),
                DB::raw('ROUND(AVG(rating_communication), 1) as avg_communication'),
                DB::raw('ROUND(AVG(rating_shipping), 1) as avg_shipping'),
                DB::raw('ROUND(AVG(rating_packaging), 1) as avg_packaging'),
                DB::raw('SUM(rating = 5) as r5'),
                DB::raw('SUM(rating = 4) as r4'),
                DB::raw('SUM(rating = 3) as r3'),
                DB::raw('SUM(rating = 2) as r2'),
                DB::raw('SUM(rating = 1) as r1'),
            )
            ->where('store_id', $storeId)
            ->first();

        return [
            'average'              => (float) ($row->average ?? 0),
            'count'                => (int)   ($row->total ?? 0),
            'avg_communication'    => (float) ($row->avg_communication ?? 0),
            'avg_shipping'         => (float) ($row->avg_shipping ?? 0),
            'avg_packaging'        => (float) ($row->avg_packaging ?? 0),
            'distribution' => [
                5 => (int) ($row->r5 ?? 0),
                4 => (int) ($row->r4 ?? 0),
                3 => (int) ($row->r3 ?? 0),
                2 => (int) ($row->r2 ?? 0),
                1 => (int) ($row->r1 ?? 0),
            ],
        ];
    }
}
