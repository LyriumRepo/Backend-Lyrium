<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'tipo_metodo',
        'documento',
        'titular',
        'detalle_extra',
        'is_default',
        'ruc_dni',
        'razon_social',
        'direccion_fiscal',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
