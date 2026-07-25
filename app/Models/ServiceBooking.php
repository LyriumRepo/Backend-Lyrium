<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class ServiceBooking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_ON_THE_WAY = 'on_the_way';

    protected $fillable = [
        'service_id',
        'user_id',
        'schedule_id',
        'appointment_date',
        'status',
        'total_price',
        'payment_method',
        'payment_status',
        'customer_notes',
        'service_address',
        'seller_notes',
        'specialist_id',
        'reschedule_token',
        'google_event_id',
        'google_event_id_client',
        'google_event_id_seller',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
        'customer_validated_at',
        'validation_source',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
            'total_price' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'customer_validated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Registrar el momento exacto en que la reserva llega a "completed",
        // sin importar por qué vía se actualizó el status.
        static::updating(function (ServiceBooking $booking) {
            if (
                $booking->isDirty('status')
                && $booking->status === self::STATUS_COMPLETED
                && $booking->completed_at === null
            ) {
                $booking->completed_at = now();
            }
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ServiceSchedule::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'service_booking_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isOnTheWay(): bool
    {
        return $this->status === self::STATUS_ON_THE_WAY;
    }

    public function canCancel(): bool
    {
        if ($this->status === self::STATUS_CANCELLED || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $cancellationDeadline = $this->appointment_date->subHours(24);

        return now() < $cancellationDeadline;
    }

    public function canReschedule(): bool
    {
        if (! $this->isConfirmed()) {
            return false;
        }

        return now() < $this->appointment_date->subHours(12);
    }

    public function canConfirm(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canMarkOnTheWay(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_CONFIRMED || $this->status === self::STATUS_ON_THE_WAY;
    }

    public function canBeCustomerValidated(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->customer_validated_at === null;
    }

    public function isCustomerValidated(): bool
    {
        return $this->customer_validated_at !== null;
    }

    public function markAsOnTheWay(): void
    {
        $this->update(['status' => self::STATUS_ON_THE_WAY]);
    }

    public function markAsNoShow(): void
    {
        $this->update(['status' => self::STATUS_NO_SHOW]);
    }

    public function generateRescheduleToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['reschedule_token' => $token]);

        return $token;
    }
}
