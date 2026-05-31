<?php

declare(strict_types=1);

namespace App\Models;

/**
 * ARCHIVO: app/Models/IzipayOrderTransaction.php
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IzipayOrderTransaction extends Model
{
    protected $table = 'izipay_order_transactions';

    protected $fillable = [
        'order_id',
        'user_id',
        'form_token',
        'izipay_order_id',
        'transaction_uuid',
        'status',
        'transaction_status',
        'amount_in_cents',
        'currency',
        'payment_method_type',
        'card_brand',
        'card_last4',
        'kr_hash',
        'izipay_response',
        'error_code',
        'error_message',
        'mode',
    ];

    protected function casts(): array
    {
        return [
            'amount_in_cents' => 'integer',
            'izipay_response' => 'array',
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Convierte soles a céntimos.
     * Izipay siempre recibe el monto en céntimos (entero).
     * Ejemplo: 149.90 → 14990
     */
    public static function toCents(float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    /**
     * Genera un orderId único para enviar a Izipay.
     * Formato: LYR-{order_id}-{timestamp}
     */
    public static function generateIzipayOrderId(int $orderId): string
    {
        return 'LYR-'.$orderId.'-'.time();
    }
}
