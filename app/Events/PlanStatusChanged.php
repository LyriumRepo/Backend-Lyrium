<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PlanStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Subscription $subscription) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('store.'.$this->subscription->store_id)];
    }

    public function broadcastWith(): array
    {
        return ['subscription' => new SubscriptionResource($this->subscription->loadMissing('plan'))];
    }
}
