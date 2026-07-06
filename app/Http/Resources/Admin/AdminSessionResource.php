<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'device' => $this->device,
            'browser' => $this->browser,
            'last_activity' => $this->last_activity
                ? \Illuminate\Support\Carbon::createFromTimestamp($this->last_activity)->toISOString()
                : null,
            'is_active' => $this->last_activity
                ? \Illuminate\Support\Carbon::createFromTimestamp($this->last_activity)->timestamp >= now()->subMinutes(15)->timestamp
                : false,

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
        ];
    }
}
