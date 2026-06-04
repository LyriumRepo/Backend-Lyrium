<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReviewReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => (string) $this->id,
            'reason' => $this->reason,
            'details' => $this->details,
            'status' => $this->status,
            'review' => $this->whenLoaded('review', fn() => [
                'id'      => (string) $this->review->id,
                'rating'  => $this->review->rating,
                'title'   => $this->review->title,
                'comment' => $this->review->comment,
                'user'    => $this->review->relationLoaded('user') ? [
                    'id'   => (string) $this->review->user->id,
                    'name' => $this->review->user->name,
                ] : null,
                'product' => $this->review->relationLoaded('product') ? [
                    'id'   => (string) $this->review->product->id,
                    'name' => $this->review->product->name,
                    'slug' => $this->review->product->slug,
                ] : null,
            ]),
            'reporter' => $this->whenLoaded('reporter', fn() => [
                'id'   => (string) $this->reporter->id,
                'name' => $this->reporter->name,
            ]),
            'moderator' => $this->whenLoaded('moderator', fn() => [
                'id'   => (string) $this->moderator->id,
                'name' => $this->moderator->name,
            ]),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
