<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ServiceStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Service $service) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('store.'.$this->service->store_id)];
    }

    public function broadcastWith(): array
    {
        return ['service' => new ServiceResource($this->service)];
    }
}
