<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BoxType
 *
 * Tipos de caja usados por BoxCalculatorService.
 * Seeded por LogisticsSeeder — código también tiene fallback hardcoded.
 */
class BoxType extends Model
{
    protected $table = 'box_types';

    protected $fillable = [
        'nombre',
        'largo',
        'ancho',
        'alto',
        'peso_max_kg',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'largo'       => 'integer',
            'ancho'       => 'integer',
            'alto'        => 'integer',
            'peso_max_kg' => 'decimal:2',
            'orden'       => 'integer',
            'activo'      => 'boolean',
        ];
    }

    /** Volumen de la caja en cm³ */
    public function getVolumenAttribute(): int
    {
        return $this->largo * $this->ancho * $this->alto;
    }

    /** Peso volumétrico estándar courier (÷5000) */
    public function getPesoVolumetricoAttribute(): float
    {
        return round($this->volumen / 5000, 3);
    }
}
