<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'ruc',
        'type',
        'especialidad',
        'status',
        'fecha_renovacion',
        'proyectos',
        'certificaciones',
        'total_gastado',
        'total_recibos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_renovacion' => 'date',
            'proyectos' => 'array',
            'certificaciones' => 'array',
            'total_gastado' => 'decimal:2',
            'total_recibos' => 'integer',
        ];
    }
}
