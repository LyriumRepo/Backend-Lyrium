<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class IzipayPlanTransaction extends Model
{
    protected $table = 'izipay_plan_transactions';

    protected $fillable = [
        'plan_request_id',
        'user_id',
        'store_id',
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

    public function planRequest(): BelongsTo
    {
        return $this->belongsTo(PlanRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public static function toCents(float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function generateIzipayOrderId(): string
    {
        return 'PLAN-'.strtoupper(Str::random(12));
    }
}
