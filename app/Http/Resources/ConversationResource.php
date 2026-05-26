<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestMessage = $this->relationLoaded('latestMessage')
            ? $this->latestMessage->first()
            : $this->messages()->latest()->first();

        return [
            'id' => (string) $this->id,
            'seller_id' => (string) $this->store?->owner?->id ?? '',
            'seller_name' => $this->store?->owner?->name ?? '',
            'seller_store' => $this->store?->trade_name ?? $this->store?->business_name ?? '',
            'seller_avatar' => $this->store?->owner?->avatar ?? '',
            'last_message' => $latestMessage?->content ?? '',
            'last_message_time' => ($latestMessage?->created_at ?? $this->created_at)?->toIso8601String(),
            'unread_count' => $this->unreadCountFor($request->user()?->id ?? 0),
            'status' => $this->status,
            'category' => $this->category,
            'subject' => $this->subject,
            'messages' => ConversationMessageResource::collection(
                $this->whenLoaded('messages')
            ),
        ];
    }
}
