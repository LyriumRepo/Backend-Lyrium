<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\ServiceBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ServiceBooking $booking,
        private readonly string $role, // 'seller' | 'client'
    ) {}

    public function via(object $notifiable): array
    {
        $settings = $notifiable->notificationSetting;
        $channels = ['database'];

        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $serviceName = $this->booking->service?->name ?? 'servicio';
        $date        = $this->booking->appointment_date?->format('d/m/Y H:i') ?? '—';
        $storeName   = $this->booking->service?->store?->trade_name ?? '—';

        if ($this->role === 'seller') {
            return (new MailMessage)
                ->subject('❌ Reserva cancelada — ' . $serviceName . ' · Lyrium BioMarketplace')
                ->view('emails.notifications.booking-cancelled', [
                    'name'        => $notifiable->name,
                    'role'        => 'seller',
                    'serviceName' => $serviceName,
                    'storeName'   => $storeName,
                    'date'        => $date,
                    'actionUrl'   => config('app.frontend_url') . '/seller/bookings',
                ]);
        }

        return (new MailMessage)
            ->subject('❌ Tu reserva fue cancelada — ' . $serviceName . ' · Lyrium BioMarketplace')
            ->view('emails.notifications.booking-cancelled', [
                'name'        => $notifiable->name,
                'role'        => 'client',
                'serviceName' => $serviceName,
                'storeName'   => $storeName,
                'date'        => $date,
                'actionUrl'   => config('app.frontend_url') . '/customer/orders',
            ]);
    }

    public function toPush(object $notifiable): array
    {
        $serviceName = $this->booking->service?->name ?? 'servicio';
        $date        = $this->booking->appointment_date?->format('d/m/Y H:i') ?? '—';

        if ($this->role === 'seller') {
            return [
                'title' => '❌ Reserva cancelada',
                'body'  => "La reserva de \"{$serviceName}\" del {$date} fue cancelada por el cliente.",
                'data'  => [
                    'type'       => 'booking_cancelled_seller',
                    'booking_id' => (string) $this->booking->id,
                    'url'        => '/seller/bookings',
                ],
            ];
        }

        return [
            'title' => '❌ Reserva cancelada',
            'body'  => "Tu reserva de \"{$serviceName}\" para el {$date} ha sido cancelada.",
            'data'  => [
                'type'       => 'booking_cancelled_client',
                'booking_id' => (string) $this->booking->id,
                'url'        => '/customer/orders',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'booking_cancelled',
            'role'             => $this->role,
            'booking_id'       => $this->booking->id,
            'service_name'     => $this->booking->service?->name ?? '—',
            'appointment_date' => $this->booking->appointment_date?->toIso8601String(),
        ];
    }
}
