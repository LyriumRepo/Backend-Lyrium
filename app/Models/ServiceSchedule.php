<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServiceSchedule extends Model
{
    use HasFactory;

    public const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    protected $fillable = [
        'service_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_appointments',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'max_appointments' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function isAvailableForBooking(string $dateTime): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $date = \Carbon\Carbon::parse($dateTime);
        $dayOfWeek = strtolower($date->format('l'));
        $time = $date->format('H:i');

        if ($this->day_of_week !== $dayOfWeek) {
            return false;
        }

        if ($time < $this->start_time || $time >= $this->end_time) {
            return false;
        }

        $bookedCount = $this->bookings()
            ->whereDate('appointment_date', $date->toDateString())
            ->whereTime('appointment_date', $time)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        return $bookedCount < $this->max_appointments;
    }
}
