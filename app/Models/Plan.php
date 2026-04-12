<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_fee',
        'commission_rate',
        'has_membership_fee',
        'features',
        'detailed_benefits',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'has_membership_fee' => 'boolean',
            'features' => 'array',
            'detailed_benefits' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
