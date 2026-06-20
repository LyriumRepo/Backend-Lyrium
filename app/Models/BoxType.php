<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public function getVolumenAttribute(): int
    {
        return $this->largo * $this->ancho * $this->alto;
    }
    public function getPesoVolumetricoAttribute(): float
    {
        return round($this->volumen / 5000, 3);
    }
}
