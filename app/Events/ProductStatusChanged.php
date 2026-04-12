<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Product $product) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('store.'.$this->product->store_id)];
    }

    public function broadcastWith(): array
    {
        return ['product' => new ProductResource($this->product)];
    }
}
