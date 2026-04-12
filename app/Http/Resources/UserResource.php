<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'nicename' => $this->nicename ?? $this->username,
            'display_name' => $this->name,
            'role' => $this->frontend_role,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'is_banned' => (bool) $this->is_banned,
            'email_verified' => ! is_null($this->email_verified_at),
            'created_at' => $this->created_at?->toIso8601String(),
            'stores_count' => $this->when(
                $this->frontend_role === 'seller',
                fn () => $this->owned_stores_count ?? $this->ownedStores()->count()
            ),
            'stores' => $this->whenLoaded(
                'ownedStores',
                fn () => $this->ownedStores
                    ->map(fn ($store) => [
                        'id' => $store->id,
                        'store_name' => $store->store_name,
                        'status' => $store->status,
                        'registered_at' => $store->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all()
            ),
        ];
    }
}
