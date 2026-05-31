<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'module' => $this->module,
            'description' => $this->description,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->toISOString(),

            'actor' => [
                'id' => $this->user_id,
                'email' => $this->user_email,
                'role' => $this->user_role,
            ],

            'auditable' => $this->when(
                $this->auditable_type !== null,
                fn () => [
                    'type' => class_basename($this->auditable_type),
                    'id' => $this->auditable_id,
                ]
            ),
        ];
    }
}
