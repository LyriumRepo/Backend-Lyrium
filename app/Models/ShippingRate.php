<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_method_id',
        'zone_id',
        'weight_from',
        'weight_to',
        'price',
        'estimated_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight_from' => 'decimal:2',
            'weight_to' => 'decimal:2',
            'price' => 'decimal:2',
            'estimated_days' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }

    public function appliesToWeight(float $weight): bool
    {
        if ($weight < $this->weight_from) {
            return false;
        }

        if ($this->weight_to !== null && $weight > $this->weight_to) {
            return false;
        }

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
