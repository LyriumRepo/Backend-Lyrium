<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserRedeemedReward extends Model
{
    use HasFactory;

    protected $table = 'user_redeemed_rewards';

    protected $fillable = [
        'user_id',
        'reward_id',
        'code',
        'discount_value',
        'is_used',
        'used_at',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'is_used' => 'boolean',
            'used_at' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public static function generateCode(): string
    {
        return strtoupper('LYR-'.bin2hex(random_bytes(4)));
    }

    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        $this->reward->increment('uses_count');
    }

    public function isValid(): bool
    {
        if ($this->is_used) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }
}
