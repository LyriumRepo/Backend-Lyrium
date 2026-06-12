<?php

declare(strict_types=1);

namespace App\Models;

/**
 * ARCHIVO: app/Models/CulqiTransaction.php
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CulqiTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'culqi_charge_id',
        'culqi_token',
        'culqi_order_id',
        'status',
        'amount',
        'amount_in_cents',
        'currency',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'email',
        'culqi_response',
        'error_code',
        'error_message',
        'source',
        'mode',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_in_cents' => 'integer',
            'culqi_response' => 'array',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Convierte soles a céntimos para Culqi.
     * Culqi siempre recibe el monto en céntimos (entero).
     * Ejemplo: 61.50 soles → 6150 céntimos
     */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
