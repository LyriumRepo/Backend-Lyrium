<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class GlossaryEntry extends Model
{
    protected $fillable = [
        'key',
        'description',
        'search_patterns',
        'default_amount',
        'account_reference',
        'is_income',
        'status',
        'source',
        'suggested_supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'search_patterns' => 'array',
            'default_amount' => 'decimal:2',
            'is_income' => 'boolean',
        ];
    }
}
