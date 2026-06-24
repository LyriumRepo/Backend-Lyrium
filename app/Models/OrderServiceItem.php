<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderServiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_booking_id',
        'service_id',
        'store_id',
        'specialist_id',
        'service_name',
        'quantity',
        'unit_price',
        'line_total',
        'status',
        'appointment_date',
        'modality',
        'duration_minutes',
        'service_snapshot',
        'store_name_snapshot',
        'specialist_name_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'appointment_date' => 'datetime',
            'duration_minutes' => 'integer',
            'service_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serviceBooking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }
}
