<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShippingMethod extends Model
{
    use HasFactory;

    public const TYPE_STANDARD = 'standard';

    public const TYPE_EXPRESS = 'express';

    public const TYPE_OVERNIGHT = 'overnight';

    public const TYPE_PICKUP = 'pickup';

    public const TYPE_FREE = 'free';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'base_cost',
        'free_shipping_min',
        'estimated_days',
        'allows_tracking',
        'provider_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'free_shipping_min' => 'decimal:2',
            'estimated_days' => 'integer',
            'allows_tracking' => 'boolean',
            'provider_config' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'shipping_method_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_shipping_methods')
            ->withPivot('is_enabled', 'additional_cost', 'handling_time_days')
            ->withTimestamps();
    }

    public function isFreeShippingEligible(float $orderTotal): bool
    {
        return $this->free_shipping_min !== null && $orderTotal >= $this->free_shipping_min;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->whereHas('zones', fn ($q) => $q->where('is_active', true));
    }
}
