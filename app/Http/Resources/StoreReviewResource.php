<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StoreReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'rating' => (int) $this->rating,
            'rating_communication' => $this->rating_communication,
            'rating_shipping' => $this->rating_shipping,
            'rating_packaging' => $this->rating_packaging,
            'title' => $this->title,
            'comment' => $this->comment,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => (string) $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar ?? null,
            ]),
            'order_id' => $this->order_id ? (string) $this->order_id : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
