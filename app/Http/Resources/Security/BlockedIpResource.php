<?php

declare(strict_types=1);

namespace App\Http\Resources\Security;

use App\Models\BlockedIp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlockedIpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'reason' => $this->reason,
            'status' => $this->status,
            'blocked_at' => $this->blocked_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'unblocked_at' => $this->unblocked_at?->toISOString(),
            'is_active' => $this->status === BlockedIp::STATUS_BLOCKED
                && ($this->expires_at === null || $this->expires_at->isFuture()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'blocker' => $this->whenLoaded('blocker', fn () => [
                'id' => $this->blocker->id,
                'name' => $this->blocker->name,
                'email' => $this->blocker->email,
            ]),

            'unblocker' => $this->whenLoaded('unblocker', fn () => [
                'id' => $this->unblocker->id,
                'name' => $this->unblocker->name,
                'email' => $this->unblocker->email,
            ]),
        ];
    }
}
