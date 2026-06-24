<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class OrderPaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '¡Pago confirmado!',
            'body' => "Tu compra #{$this->order->order_number} fue procesada. Ingresa a tu panel para seguirla.",
            'data' => [
                'type' => 'payment_confirmed',
                'order_id' => (string) $this->order->id,
                'order_number' => (string) $this->order->order_number,
                'url' => '/customer/orders',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_confirmed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => (float) $this->order->total,
            'subject' => "Pago confirmado — Pedido #{$this->order->order_number}",
            'url' => '/customer/orders',
        ];
    }
}
