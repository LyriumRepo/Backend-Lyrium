<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Service extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const CANCELLATION_NO_REFUND = 'no_refund';

    public const CANCELLATION_FLEXIBLE = 'flexible';

    public const CANCELLATION_STRICT = 'strict';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'duration_minutes',
        'buffer_minutes',           // 👈 Nuevo
        'is_home_service',         // 👈 Nuevo
        'booking_advance_hours',    // 👈 Nuevo
        'max_capacity',
        'status',
        'cancellation_policy',
        'max_cancellations',
        'settings',
        'google_calendar_id'
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'settings' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function specialists(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Specialist::class, 'service_specialist');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canCancelWithoutRefund(): bool
    {
        return $this->cancellation_policy === self::CANCELLATION_NO_REFUND;
    }

    public function isFlexibleCancellation(): bool
    {
        return $this->cancellation_policy === self::CANCELLATION_FLEXIBLE;
    }

    public function getNextAvailableSlot(string $date): ?array
    {
        $dayOfWeek = strtolower(now()->parse($date)->format('l'));

        $schedule = $this->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $schedule) {
            return null;
        }

        $bookedSlots = $this->bookings()
            ->whereDate('appointment_date', $date)
            ->where('schedule_id', $schedule->id)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('appointment_date')
            ->map(fn($dt) => $dt->format('H:i'))
            ->toArray();

        $availableSlots = [];
        $current = $schedule->start_time;
        $end = $schedule->end_time;

        while ($current < $end) {
            $time = \Carbon\Carbon::parse($current)->format('H:i');
            if (! in_array($time, $bookedSlots)) {
                $availableSlots[] = $time;
            }
            $current = \Carbon\Carbon::parse($current)->addMinutes($this->duration_minutes)->format('H:i');
        }

        return empty($availableSlots) ? null : $availableSlots;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->singleFile();
    }
}
