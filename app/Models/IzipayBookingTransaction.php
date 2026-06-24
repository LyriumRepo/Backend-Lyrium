<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IzipayBookingTransaction extends Model
{
    protected $table = 'izipay_booking_transactions';

    protected $fillable = [
        'user_id',
        'service_id',
        'schedule_id',
        'specialist_id',
        'appointment_date',
        'customer_notes',
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
        'error_code',
        'error_message',
        'mode',
        'kr_hash',
        'izipay_response',
        'booking_id',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
            'amount_in_cents' => 'integer',
            'izipay_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ServiceSchedule::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class);
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

    public static function generateIzipayOrderId(int $serviceId): string
    {
        return 'BKG-'.$serviceId.'-'.time();
    }
}
