<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DisputeMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispute_id' => $this->dispute_id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'is_internal' => $this->is_internal,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'attachments' => $this->whenLoaded('attachments', fn () => DisputeAttachmentResource::collection($this->attachments)),
        ];
    }
}
