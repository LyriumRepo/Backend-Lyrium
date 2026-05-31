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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'date',
        'paid_at' => 'date',
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
    public static function nextReceiptNumber(): string
    {
        $year = now()->year;
        $count = self::whereYear('created_at', $year)->withTrashed()->count();

        return sprintf('EXP-%d-%03d', $year, $count + 1);
    }
}
