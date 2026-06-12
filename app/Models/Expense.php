<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'supplier_id',
        'concept',
        'amount',
        'status',
        'issued_at',
        'paid_at',
        'voucher_type',
        'voucher_number',
        'file_url',
        'registered_by',
        'notes',
        'scan_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'date',
        'paid_at' => 'date',
        'scan_data' => 'array',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'Pagado');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pendiente');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Genera el próximo número de recibo: EXP-{YYYY}-{NNN}
     */
    public static function nextReceiptNumber(?string $voucherType = null): string
    {
        $year = date('Y'); // 2026

        // 1. Determinar el prefijo según el tipo de comprobante
        $prefix = match ($voucherType) {
            'Factura'    => 'FAC',
            'Boleta'     => 'BOL',
            'Honorarios' => 'HON',
            default      => 'EXP', // Resguardo por si llega nulo o es otro tipo (Servicio, etc.)
        };

        // 2. Buscar el último registro en la BD que use el prefijo de este año
        // Ejemplo de búsqueda: "FAC-2026-%"
        $lastExpense = self::where('receipt_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('receipt_number', 'desc')
            ->first();

        if (!$lastExpense) {
            // Si es el primer comprobante de este tipo en el año, empezamos en 001
            return "{$prefix}-{$year}-001";
        }

        // 3. Extraer el número actual e incrementarlo
        $parts = explode('-', $lastExpense->receipt_number);
        $lastSegment = end($parts);

        $nextNumber = (int)$lastSegment + 1; // Aquí es un 'int'

        // 4. Armar el nuevo código transformando el 'int' a 'string'
        // Agregamos (string) antes de la variable para corregir el linter
        return "{$prefix}-{$year}-" . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
