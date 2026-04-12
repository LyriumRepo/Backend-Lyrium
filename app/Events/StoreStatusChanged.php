<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\StoreResource;
use App\Models\Store;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StoreStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Store $store) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('store.'.$this->store->id)];
    }

    public function broadcastWith(): array
    {
        return ['store' => new StoreResource($this->store)];
    }
}
