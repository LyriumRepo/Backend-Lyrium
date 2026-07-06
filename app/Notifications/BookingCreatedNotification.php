<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\ServiceBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ServiceBooking $booking,
        private readonly string $role,
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
        $date = $this->booking->appointment_date?->format('d/m/Y H:i') ?? '—';

        if ($this->role === 'seller') {
            return [
                'title' => '📅 Nueva reserva recibida',
                'body'  => "Reserva para \"{$serviceName}\" el {$date}.",
                'data'  => [
                    'type' => 'booking_created',
                    'url'  => '/seller/services',
                ],
            ];
        }

        return [
            'title' => '✅ Reserva registrada',
            'body'  => "Tu reserva para \"{$serviceName}\" el {$date} fue registrada.",
            'data'  => [
                'type' => 'booking_created',
                'url'  => '/customer/orders',
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

        if ($this->role === 'seller') {
            return (new MailMessage)
                ->subject('Nueva reserva recibida - ' . $serviceName . ' - Lyrium')
                ->greeting('¡Hola, ' . $notifiable->name . '!')
                ->line('Has recibido una nueva reserva para tu servicio.')
                ->line('**Servicio:** ' . $serviceName)
                ->line('**Cliente:** ' . ($this->booking->user?->name ?? '—'))
                ->line('**Fecha:** ' . $date)
                ->action('Ver mis reservas', config('app.frontend_url') . '/seller/services');
        }

        return (new MailMessage)
            ->subject('Tu reserva fue registrada - ' . $serviceName . ' - Lyrium')
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('Tu reserva ha sido registrada correctamente.')
            ->line('**Servicio:** ' . $serviceName)
            ->line('**Proveedor:** ' . $storeName)
            ->line('**Fecha:** ' . $date)
            ->action('Ver mis pedidos', config('app.frontend_url') . '/customer/orders')
            ->line('Te notificaremos cuando el centro de salud confirme tu reserva.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_created',
            'role' => $this->role,
            'booking_id' => $this->booking->id,
            'service_name' => $this->booking->service?->name ?? '—',
            'customer_name' => $this->booking->user?->name ?? '—',
            'appointment_date' => $this->booking->appointment_date?->toIso8601String(),
            'store_name' => $this->booking->service?->store?->trade_name
                ?? $this->booking->service?->store?->store_name
                ?? '—',
        ];
    }
}
