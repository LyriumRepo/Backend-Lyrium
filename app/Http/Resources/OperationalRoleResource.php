<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OperationalRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'modules' => $this->modules ?? [],
            'requires_2fa' => $this->requires_2fa,
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
