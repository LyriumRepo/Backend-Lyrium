<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CommissionTier extends Model
{
    protected $fillable = [
        'name',
        'min_amount',
        'max_amount',
        'rate',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'rate' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
