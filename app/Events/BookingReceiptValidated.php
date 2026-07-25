<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ServiceBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BookingReceiptValidated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ServiceBooking $booking,
        public readonly string $source, // manual | email
    ) {}
}
