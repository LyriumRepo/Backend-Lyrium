<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\ServiceBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingOnTheWayNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ServiceBooking $booking,
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
        $storeName   = $this->booking->service?->store?->trade_name ?? '—';
        $date        = $this->booking->appointment_date?->format('d/m/Y H:i') ?? '—';

        return (new MailMessage)
            ->subject('🚗 El proveedor está en camino — ' . $serviceName . ' · Lyrium BioMarketplace')
            ->view('emails.notifications.booking-on-the-way', [
                'name'        => $notifiable->name,
                'serviceName' => $serviceName,
                'storeName'   => $storeName,
                'date'        => $date,
                'actionUrl'   => config('app.frontend_url') . '/customer/orders',
            ]);
    }

    public function toPush(object $notifiable): array
    {
        $serviceName = $this->booking->service?->name ?? 'servicio';
        $storeName   = $this->booking->service?->store?->trade_name ?? '—';

        return [
            'title' => '🚗 El proveedor está en camino',
            'body'  => "El equipo de \"{$storeName}\" ya va en camino para brindarte el servicio de \"{$serviceName}\".",
            'data'  => [
                'type'       => 'booking_on_the_way',
                'booking_id' => (string) $this->booking->id,
                'url'        => '/customer/orders',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'booking_on_the_way',
            'booking_id'       => $this->booking->id,
            'service_name'     => $this->booking->service?->name ?? '—',
            'appointment_date' => $this->booking->appointment_date?->toIso8601String(),
            'subject'          => 'El proveedor está en camino para tu servicio',
        ];
    }
}
