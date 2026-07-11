<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ShipmentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const STATUS_LABELS = [
        'pending'          => 'Pendiente de envío',
        'picked_up'        => 'Paquete recogido',
        'in_transit'       => 'En tránsito',
        'out_for_delivery' => 'En camino a entrega',
        'delivered'        => 'Entregado',
        'failed'           => 'Intento de entrega fallido',
        'returned'         => 'Devuelto al remitente',
    ];

    private const STATUS_ICONS = [
        'pending'          => '📦',
        'picked_up'        => '🚚',
        'in_transit'       => '🔄',
        'out_for_delivery' => '🏃',
        'delivered'        => '✅',
        'failed'           => '❌',
        'returned'         => '↩️',
    ];

    public function __construct(
        private readonly Shipment $shipment,
        private readonly string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $icon  = self::STATUS_ICONS[$this->newStatus] ?? '📦';
        $label = self::STATUS_LABELS[$this->newStatus] ?? $this->newStatus;

        return [
            'title' => "{$icon} Envío actualizado",
            'body'  => "Tu pedido #{$this->shipment->order?->order_number}: {$label}",
            'data'  => [
                'type'     => 'shipment_status',
                'status'   => $this->newStatus,
                'order_id' => (string) ($this->shipment->order_id ?? ''),
                'url'      => '/customer/orders',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $icon  = self::STATUS_ICONS[$this->newStatus] ?? '📦';
        $label = self::STATUS_LABELS[$this->newStatus] ?? $this->newStatus;
        $orderNumber = $this->shipment->order?->order_number ?? '—';

        return (new MailMessage)
            ->subject("{$icon} Estado de envío: {$label} — Pedido #{$orderNumber}")
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line("El estado de tu envío del pedido **#{$orderNumber}** ha cambiado.")
            ->line("**Estado:** {$icon} {$label}")
            ->when(
                $this->shipment->tracking_number,
                fn ($m) => $m->line("**Código de seguimiento:** {$this->shipment->tracking_number}")
            )
            ->when(
                $this->shipment->carrier,
                fn ($m) => $m->line("**Transportista:** {$this->shipment->carrier}")
            )
            ->action('Seguir mi pedido', config('app.frontend_url') . '/customer/orders');
    }

    public function toArray(object $notifiable): array
    {
        $label = self::STATUS_LABELS[$this->newStatus] ?? $this->newStatus;

        return [
            'type'            => 'shipment_status',
            'title'           => "📦 Envío actualizado: {$label}",
            'message'         => "El envío de tu pedido #{$this->shipment->order?->order_number} está ahora en estado \"{$label}\".",
            'shipment_id'     => $this->shipment->id,
            'order_id'        => $this->shipment->order_id,
            'order_number'    => $this->shipment->order?->order_number,
            'status'          => $this->newStatus,
            'status_label'    => $label,
            'tracking_number' => $this->shipment->tracking_number,
            'carrier'         => $this->shipment->carrier,
            'action_url'      => '/customer/orders',
        ];
    }
}
