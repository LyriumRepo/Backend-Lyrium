<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ServiceHold extends Model
{
    protected $fillable = [
        'cart_token',
        'service_id',
        'service_name',
        'service_price',
        'service_image',
        'specialist_id',
        'specialist_name',
        'schedule_id',
        'appointment_date',
        'start_time',
        'customer_notes',
        'service_address',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'service_price' => 'decimal:2',
            'appointment_date' => 'date:Y-m-d',
            'expires_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeForCart($query, string $cartToken)
    {
        return $query->where('cart_token', $cartToken)->active();
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function secondsRemaining(): int
    {
        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }
}
