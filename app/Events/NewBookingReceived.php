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

    private readonly int $storeId;

    public function __construct(public readonly ServiceBooking $booking)
    {
        $this->storeId = $booking->service?->store_id
            ?? \App\Models\OrderServiceItem::where('service_booking_id', $booking->id)->value('store_id')
            ?? 0;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('store.'.$this->storeId)];
    }

    public function broadcastWith(): array
    {
        return ['booking' => new ServiceBookingResource($this->booking->loadMissing(['service', 'user']))];
    }
}
