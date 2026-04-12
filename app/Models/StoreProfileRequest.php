<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StoreProfileRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const MAX_ATTEMPTS = 3;

    public const REJECTION_COOLDOWN_HOURS = 24;

    protected $fillable = [
        'store_id',
        'data',
        'status',
        'admin_notes',
        'reviewed_by',
        'attempts',
        'last_rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'last_rejected_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function canRetry(): bool
    {
        if ($this->status !== self::STATUS_REJECTED) {
            return false;
        }

        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $cooldown = now()->subHours(self::REJECTION_COOLDOWN_HOURS);

            return $this->last_rejected_at?->isBefore($cooldown);
        }

        return true;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
