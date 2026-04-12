<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'points_per_currency',
        'currency_per_point',
        'min_points_to_redeem',
    ];

    protected function casts(): array
    {
        return [
            'points_per_currency' => 'decimal:2',
            'currency_per_point' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(LoyaltyTier::class)->orderBy('min_points', 'asc');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(LoyaltyReward::class);
    }

    public function activeRewards()
    {
        return $this->rewards()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    public function defaultTier()
    {
        return $this->tiers()->where('is_default', true);
    }

    public function calculatePointsForAmount(float $amount): int
    {
        return (int) floor($amount * $this->points_per_currency);
    }

    public function calculateValueForPoints(int $points): float
    {
        return $points * $this->currency_per_point;
    }
}
