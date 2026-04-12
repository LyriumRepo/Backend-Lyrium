<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StoreShippingMethod extends Model
{
    protected $table = 'store_shipping_methods';

    protected $fillable = [
        'store_id',
        'shipping_method_id',
        'is_enabled',
        'additional_cost',
        'handling_time_days',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'additional_cost' => 'decimal:2',
            'handling_time_days' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function getTotalCost(float $baseCost): float
    {
        return $baseCost + (float) $this->additional_cost;
    }
}
