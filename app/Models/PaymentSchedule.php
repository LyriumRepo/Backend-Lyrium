<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentSchedule extends Model
{
    use HasFactory;

    public const DAY_MONDAY = 'monday';

    public const DAY_TUESDAY = 'tuesday';

    public const DAY_WEDNESDAY = 'wednesday';

    public const DAY_THURSDAY = 'thursday';

    public const DAY_FRIDAY = 'friday';

    public const DAY_SATURDAY = 'saturday';

    public const DAY_SUNDAY = 'sunday';

    public const DAYS = [
        self::DAY_MONDAY,
        self::DAY_TUESDAY,
        self::DAY_WEDNESDAY,
        self::DAY_THURSDAY,
        self::DAY_FRIDAY,
        self::DAY_SATURDAY,
        self::DAY_SUNDAY,
    ];

    protected $fillable = [
        'name',
        'day',
        'cutoff_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cutoff_time' => 'datetime:H:i:s',
            'is_active' => 'boolean',
        ];
    }

    public function isToday(): bool
    {
        return strtolower(now()->dayName) === $this->day;
    }

    public function isActiveToday(): bool
    {
        return $this->is_active && $this->isToday();
    }

    public function getNextPaymentDate(): \Carbon\Carbon
    {
        $today = now();
        $currentDay = strtolower($today->dayName);
        $days = self::DAYS;

        $currentIndex = array_search($currentDay, $days);
        $scheduleIndex = array_search($this->day, $days);

        if ($currentIndex === false || $scheduleIndex === false) {
            return $today->copy()->addDay();
        }

        if ($scheduleIndex > $currentIndex) {
            return $today->copy()->addDays($scheduleIndex - $currentIndex);
        }

        return $today->copy()->addDays((7 - $currentIndex) + $scheduleIndex);
    }

    public static function getActiveDays(): array
    {
        return self::query()
            ->where('is_active', true)
            ->pluck('day')
            ->toArray();
    }

    public static function isPaymentDayToday(): bool
    {
        $today = strtolower(now()->dayName);

        return self::query()
            ->where('is_active', true)
            ->where('day', $today)
            ->exists();
    }
}
