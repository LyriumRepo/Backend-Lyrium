<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'enlace',
        'imagen',
        'imagen_mobile',
        'seccion',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySection($query, string $section)
    {
        return $query->where('seccion', $section);
    }
}
