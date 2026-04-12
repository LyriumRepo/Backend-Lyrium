<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LoyaltyTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'name',
        'min_points',
        'bonus_rate',
        'benefits',
        'icon',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'min_points' => 'integer',
            'bonus_rate' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(UserLoyaltyAccount::class, 'tier_id');
    }
}
