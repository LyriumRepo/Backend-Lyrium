<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ServiceBooking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

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
        'seller_notes',
        'reschedule_token',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
            'total_price' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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
