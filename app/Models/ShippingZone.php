<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'region',
        'department',
        'districts',
        'min_weight',
        'max_weight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'districts' => 'array',
            'min_weight' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'zone_id');
    }

    public function coversDepartment(string $department): bool
    {
        return $this->department === $department;
    }

    public function coversDistrict(string $district): bool
    {
        return in_array($district, $this->districts ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRegion($query, string $region)
    {
        return $query->where('region', $region);
    }
}
