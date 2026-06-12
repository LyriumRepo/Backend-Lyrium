<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderCreatedNotification extends Notification
{
    public function __construct(
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
