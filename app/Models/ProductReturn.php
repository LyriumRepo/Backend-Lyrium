<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ProductReturn extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELLED = 'cancelled';

    public const REASON_DEFECTIVE = 'defective';

    public const REASON_WRONG_ITEM = 'wrong_item';

    public const REASON_NOT_AS_DESCRIBED = 'not_as_described';

    public const REASON_ARRIVED_DAMAGED = 'arrived_damaged';

    public const REASON_OTHER = 'other';

    protected $table = 'returns';

    protected $fillable = [
        'order_id',
        'user_id',
        'store_id',
        'return_number',
        'status',
        'reason',
        'reason_details',
        'resolution_notes',
        'refund_amount',
        'refund_method',
        'shipping_carrier',
        'tracking_number',
        'requested_at',
        'reviewed_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canCancel(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canReject(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canMarkReceived(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canRefund(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public static function generateReturnNumber(): string
    {
        return 'RET-'.date('Ymd').'-'.strtoupper(uniqid());
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
