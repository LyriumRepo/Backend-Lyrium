<?php

declare(strict_types=1);

namespace App\Catalogs;

final class BookingEvents
{
    const CREATED = 'bookings.created';
    const CONFIRMED = 'bookings.confirmed';
    const CANCELLED = 'bookings.cancelled';
    const RESCHEDULED = 'bookings.rescheduled';
    const COMPLETED = 'bookings.completed';
    const NO_SHOW = 'bookings.no.show';
}
