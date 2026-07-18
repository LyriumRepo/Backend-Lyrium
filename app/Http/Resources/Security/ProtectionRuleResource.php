<?php

declare(strict_types=1);

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProtectionRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'pattern' => $this->pattern,
            'severity' => $this->severity,
            'status' => $this->status,
            'priority' => $this->priority,
            'description' => $this->description,
            'config' => $this->config,
            'triggered_at' => $this->triggered_at?->toISOString(),
            'trigger_count' => $this->trigger_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
        ];
    }
}
