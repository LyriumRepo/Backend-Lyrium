<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NewOrderReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly int $storeId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('store.'.$this->storeId)];
    }

    public function broadcastWith(): array
    {
        return ['order' => new OrderResource($this->order->loadMissing(['items', 'user']))];
    }
}
