<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ServiceSlotHold extends Model
{
    protected $fillable = [
        'service_id',
        'specialist_id',
        'schedule_id',
        'appointment_date',
        'start_time',
        'cart_token',
        'customer_notes',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
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
}
