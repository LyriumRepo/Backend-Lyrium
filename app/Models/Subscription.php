<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Subscription extends Model
{
    protected $fillable = [
        'store_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',
        'auto_renew',
        'payment_method_id',
        'plan_request_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'auto_renew' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function planRequest(): BelongsTo
    {
        return $this->belongsTo(PlanRequest::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at?->isFuture() ?? false);
    }
}
