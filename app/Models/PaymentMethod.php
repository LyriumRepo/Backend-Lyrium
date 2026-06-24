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
        'card_token',
        'card_last4',
        'card_brand',
        'card_exp_month',
        'card_exp_year',
        'token_status',
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

    public function isCardTokenized(): bool
    {
        return $this->tipo_metodo === 'tarjeta'
            && $this->card_token !== null
            && $this->token_status === 'active';
    }

    public function needsUpdate(): bool
    {
        return $this->tipo_metodo === 'tarjeta'
            && $this->card_token === null;
    }

    public function scopeDefaultFirst($query)
    {
        return $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
