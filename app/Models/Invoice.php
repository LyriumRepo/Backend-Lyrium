<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Invoice extends Model
{
    use HasFactory;

    public const SUNAT_STATUS_DRAFT = 'DRAFT';

    public const SUNAT_STATUS_SENT_WAIT_CDR = 'SENT_WAIT_CDR';

    public const SUNAT_STATUS_ACCEPTED = 'ACCEPTED';

    public const SUNAT_STATUS_OBSERVED = 'OBSERVED';

    public const SUNAT_STATUS_REJECTED = 'REJECTED';

    public const TYPE_FACTURA = 'FACTURA';

    public const TYPE_BOLETA = 'BOLETA';

    public const TYPE_NOTA_CREDITO = 'NOTA_CREDITO';

    public const TYPES = [
        self::TYPE_FACTURA,
        self::TYPE_BOLETA,
        self::TYPE_NOTA_CREDITO,
    ];

    public const SUNAT_STATUSES = [
        self::SUNAT_STATUS_DRAFT,
        self::SUNAT_STATUS_SENT_WAIT_CDR,
        self::SUNAT_STATUS_ACCEPTED,
        self::SUNAT_STATUS_OBSERVED,
        self::SUNAT_STATUS_REJECTED,
    ];

    protected $fillable = [
        'store_id',
        'order_id',
        'invoice_number',
        'series',
        'number',
        'type',
        'customer_name',
        'customer_ruc',
        'document_type',
        'nit',
        'business_name',
        'customer_document_type',
        'customer_address',
        'customer_email',
        'provider',
        'provider_invoice_id',
        'qr_data',
        'pdf_url',
        'authorization_code',
        'total',
        'subtotal_sin_igv',
        'igv_amount',
        'status',
        'sunat_status',
        'emission_date',
        'history',
        'xml_url',
        'cdr_url',
        'nubefact_response',
        'items',
        'store_commissions',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'subtotal_sin_igv' => 'decimal:2',
            'igv_amount' => 'decimal:2',
            'emission_date' => 'datetime',
            'history' => 'array',
            'xml_url' => 'string',
            'cdr_url' => 'string',
            'nubefact_response' => 'array',
            'items' => 'array',
            'store_commissions' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function addHistoryEntry(string $status, string $note, string $user): void
    {
        $history = $this->history ?? [];
        $history[] = [
            'status' => $status,
            'note' => $note,
            'timestamp' => now()->format('Y-m-d H:i'),
            'user' => $user,
        ];
        $this->history = $history;
    }
}
