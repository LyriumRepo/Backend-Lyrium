<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $table = 'loyalty_transactions';

    public const TYPE_EARNED = 'earned';

    public const TYPE_REDEEMED = 'redeemed';

    public const TYPE_EXPIRED = 'expired';

    public const TYPE_ADJUSTED = 'adjusted';

    protected $fillable = [
        'account_id',
        'order_id',
        'type',
        'points',
        'points_balance_after',
        'description',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'points_balance_after' => 'integer',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UserLoyaltyAccount::class, 'account_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isEarned(): bool
    {
        return $this->type === self::TYPE_EARNED;
    }

    public function isRedeemed(): bool
    {
        return $this->type === self::TYPE_REDEEMED;
    }

    public function isExpired(): bool
    {
        return $this->type === self::TYPE_EXPIRED;
    }

    public function isExpiredPoints(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
