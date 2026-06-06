<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'document_type',
        'series',
        'number',
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
        'status',
        'nubefact_response',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'nubefact_response' => 'array',
            'items' => 'array',
        ];
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
}
