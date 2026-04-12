<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UserLoyaltyAccount extends Model
{
    use HasFactory;

    protected $table = 'user_loyalty_accounts';

    protected $fillable = [
        'user_id',
        'program_id',
        'tier_id',
        'points_balance',
        'lifetime_points',
        'points_redeemed',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'points_balance' => 'integer',
            'lifetime_points' => 'integer',
            'points_redeemed' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'account_id');
    }

    public function addPoints(int $points, ?int $orderId = null, ?string $description = null): LoyaltyTransaction
    {
        $this->increment('points_balance', $points);
        $this->increment('lifetime_points', $points);
        $this->update(['last_activity_at' => now()]);

        $transaction = $this->transactions()->create([
            'order_id' => $orderId,
            'type' => 'earned',
            'points' => $points,
            'points_balance_after' => $this->fresh()->points_balance,
            'description' => $description,
            'expires_at' => now()->addYear(),
        ]);

        $this->checkTierUpgrade();

        return $transaction;
    }

    public function redeemPoints(int $points, ?string $description = null): LoyaltyTransaction
    {
        if ($points > $this->points_balance) {
            throw new \InvalidArgumentException('Puntos insuficientes');
        }

        $this->decrement('points_balance', $points);
        $this->increment('points_redeemed', $points);
        $this->update(['last_activity_at' => now()]);

        return $this->transactions()->create([
            'type' => 'redeemed',
            'points' => -$points,
            'points_balance_after' => $this->fresh()->points_balance,
            'description' => $description,
        ]);
    }

    public function checkTierUpgrade(): void
    {
        if (! $this->tier || ! $this->program) {
            return;
        }

        $nextTier = $this->program->tiers()
            ->where('min_points', '>', $this->tier->min_points)
            ->where('min_points', '<=', $this->lifetime_points)
            ->orderBy('min_points', 'asc')
            ->first();

        if ($nextTier) {
            $this->update(['tier_id' => $nextTier->id]);
        }
    }

    public function getStatus(): array
    {
        return [
            'points_balance' => $this->points_balance,
            'lifetime_points' => $this->lifetime_points,
            'points_redeemed' => $this->points_redeemed,
            'tier' => $this->tier,
            'program' => $this->program,
            'next_tier' => $this->getNextTier(),
            'points_to_next_tier' => $this->getPointsToNextTier(),
        ];
    }

    public function getNextTier(): ?LoyaltyTier
    {
        if (! $this->tier || ! $this->program) {
            return null;
        }

        return $this->program->tiers()
            ->where('min_points', '>', $this->tier->min_points)
            ->orderBy('min_points', 'asc')
            ->first();
    }

    public function getPointsToNextTier(): ?int
    {
        $nextTier = $this->getNextTier();
        if (! $nextTier) {
            return null;
        }

        return $nextTier->min_points - $this->lifetime_points;
    }
}
