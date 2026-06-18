<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\ServiceBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ServiceBooking $booking,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $serviceName = $this->booking->service?->name ?? 'servicio';
        $date        = $this->booking->appointment_date?->format('d/m/Y H:i') ?? '—';

        return [
            'title' => '✅ Reserva confirmada',
            'body'  => "Tu reserva de \"{$serviceName}\" para el {$date} fue confirmada.",
            'data'  => [
                'type'       => 'booking_confirmed',
                'booking_id' => (string) $this->booking->id,
                'url'        => '/customer/orders',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $serviceName = $this->booking->service?->name ?? 'servicio';
        $date = $this->booking->appointment_date?->format('d/m/Y H:i') ?? '—';
        $storeName = $this->booking->service?->store?->trade_name
            ?? $this->booking->service?->store?->store_name
            ?? '—';

        return (new MailMessage)
            ->subject('¡Tu reserva fue confirmada! - ' . $serviceName . ' - Lyrium')
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('El centro de salud ha confirmado tu reserva.')
            ->line('**Servicio:** ' . $serviceName)
            ->line('**Proveedor:** ' . $storeName)
            ->line('**Fecha:** ' . $date)
            ->action('Ver mis pedidos', config('app.frontend_url') . '/customer/orders')
            ->line('Te esperamos. Si necesitas cambiar la fecha, contáctanos a través del chat.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_confirmed',
            'booking_id' => $this->booking->id,
            'service_name' => $this->booking->service?->name ?? '—',
            'appointment_date' => $this->booking->appointment_date?->toIso8601String(),
        ];
    }
}
