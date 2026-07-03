<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\AuditableModel;
use Illuminate\Support\Facades\DB;

final class Review extends Model
{
    use AuditableModel, HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'service_booking_id',
        'specialist_id',
        'rating',
        'title',
        'comment',
        'reported_count',
        'is_verified_purchase',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'reported_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serviceBooking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }

    public function pendingReports(): HasMany
    {
        return $this->hasMany(ReviewReport::class)->where('status', 'pending');
    }

    public function isReportedBy(int $userId): bool
    {
        return $this->reports()->where('reporter_id', $userId)->exists();
    }

    /**
     * Devuelve promedio, total y distribución en UNA sola query.
     */
    public static function getRatingStats(int $productId): array
    {
        $rows = DB::table('reviews')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND(AVG(rating), 1) as average'),
                DB::raw('SUM(rating = 5) as r5'),
                DB::raw('SUM(rating = 4) as r4'),
                DB::raw('SUM(rating = 3) as r3'),
                DB::raw('SUM(rating = 2) as r2'),
                DB::raw('SUM(rating = 1) as r1'),
            )
            ->where('product_id', $productId)
            ->first();

        $total = (int) ($rows->total ?? 0);

        return [
            'average' => (float) ($rows->average ?? 0),
            'count' => $total,
            'distribution' => [
                5 => (int) ($rows->r5 ?? 0),
                4 => (int) ($rows->r4 ?? 0),
                3 => (int) ($rows->r3 ?? 0),
                2 => (int) ($rows->r2 ?? 0),
                1 => (int) ($rows->r1 ?? 0),
            ],
        ];
    }
}
