<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LoyaltyReward extends Model
{
    use HasFactory;

    public const TYPE_DISCOUNT_PERCENTAGE = 'discount_percentage';

    public const TYPE_DISCOUNT_FIXED = 'discount_fixed';

    public const TYPE_FREE_SHIPPING = 'free_shipping';

    public const TYPE_FREE_PRODUCT = 'free_product';

    public const TYPES = [
        self::TYPE_DISCOUNT_PERCENTAGE,
        self::TYPE_DISCOUNT_FIXED,
        self::TYPE_FREE_SHIPPING,
        self::TYPE_FREE_PRODUCT,
    ];

    protected $fillable = [
        'program_id',
        'name',
        'description',
        'reward_type',
        'value',
        'points_required',
        'max_uses',
        'uses_count',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'points_required' => 'integer',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(UserRedeemedReward::class, 'reward_id');
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        $now = now();
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }

    public function getTypeLabel(): string
    {
        return match ($this->reward_type) {
            self::TYPE_DISCOUNT_PERCENTAGE => 'Descuento %',
            self::TYPE_DISCOUNT_FIXED => 'Descuento fijo',
            self::TYPE_FREE_SHIPPING => 'Envío gratis',
            self::TYPE_FREE_PRODUCT => 'Producto gratis',
            default => $this->reward_type,
        };
    }

    public function calculateDiscountValue(float $orderTotal): float
    {
        return match ($this->reward_type) {
            self::TYPE_DISCOUNT_PERCENTAGE => $orderTotal * ($this->value / 100),
            self::TYPE_DISCOUNT_FIXED => $this->value,
            self::TYPE_FREE_SHIPPING => 0,
            default => 0,
        };
    }
}
