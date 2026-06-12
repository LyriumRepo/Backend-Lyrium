<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->order->user_id)];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => (string) $this->order->id,
            'status' => $this->order->status,
            'active' => in_array($this->order->status, [
                Order::STATUS_PENDING_SELLER,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
            ]),
        ];
    }
}
