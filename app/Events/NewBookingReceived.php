<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\ServiceBookingResource;
use App\Models\ServiceBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NewBookingReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ServiceBooking $booking) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('store.'.$this->booking->service->store_id)];
    }

    public function broadcastWith(): array
    {
        return ['booking' => new ServiceBookingResource($this->booking->loadMissing(['service', 'user']))];
    }
}
