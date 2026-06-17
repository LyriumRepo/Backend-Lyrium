<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class OrderCreatedNotification extends Notification implements ShouldQueue
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
            'title' => '¡Pedido confirmado!',
            'body' => "Pedido #{$this->order->order_number} — S/ " . number_format((float) $this->order->total, 2),
            'data' => [
                'type' => 'order_created',
                'order_id' => (string) $this->order->id,
                'order_number' => (string) $this->order->order_number,
                'url' => '/customer/orders',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_created',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => (float) $this->order->total,
            'subject' => "Pedido #{$this->order->order_number} confirmado — S/ " . number_format((float) $this->order->total, 2),
        ];
    }
}
