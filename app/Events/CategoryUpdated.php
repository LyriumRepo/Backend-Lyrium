<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CategoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Category $category,
        public readonly string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('categories')];
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'category' => new CategoryResource($this->category),
        ];
    }
}
