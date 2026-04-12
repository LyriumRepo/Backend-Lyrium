<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'displayName' => $this->name,
            'role' => $this->frontend_role,
            'avatar' => $this->avatar,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'store' => $this->when(
                $this->relationLoaded('stores') && $this->stores->isNotEmpty(),
                fn () => new StoreResource($this->stores->first())
            ),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
