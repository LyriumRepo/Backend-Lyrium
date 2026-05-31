<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ServiceBooking $booking,
        private readonly string $role,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'role' => $this->role,
            'booking_id' => $this->booking->id,
            'service_name' => $this->booking->service?->name ?? '—',
            'appointment_date' => $this->booking->appointment_date?->toIso8601String(),
        ];
    }
}
