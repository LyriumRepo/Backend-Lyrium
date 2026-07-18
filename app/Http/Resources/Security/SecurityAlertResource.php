<?php

declare(strict_types=1);

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SecurityAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'status' => $this->status,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),

            'audit_log_id' => $this->audit_log_id,

            'resolver' => $this->whenLoaded('resolver', fn () => [
                'id' => $this->resolver->id,
                'name' => $this->resolver->name,
                'email' => $this->resolver->email,
            ]),

            'audit_log' => $this->whenLoaded('auditLog', fn () => [
                'id' => $this->auditLog->id,
                'event' => $this->auditLog->event,
                'description' => $this->auditLog->description,
            ]),
        ];
    }
}
