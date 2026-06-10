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
            ? $this->latestMessage
            : $this->messages()->latest()->first();

        return [
            'id' => (string) $this->id,
            'seller_id' => (string) $this->store?->owner?->id ?? '',
            'seller_name' => $this->store?->owner?->name ?? '',
            'seller_store' => $this->store?->trade_name ?? $this->store?->nombre_comercial ?? $this->store?->razon_social ?? '',
            'seller_avatar' => $this->store?->owner?->avatar ?? '',
            'last_message' => $latestMessage?->content ?? '',
            'last_message_time' => ($latestMessage?->created_at ?? $this->created_at)?->toIso8601String(),
            'unread_count' => $this->unreadCountFor($request->user()?->id ?? 0),
            'status' => $this->status,
            'category' => $this->category,
            'subject' => $this->subject,
            'customer_id' => (string) $this->customer_user_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name ?? ''),
            'customer_email' => $this->whenLoaded('customer', fn () => $this->customer?->email ?? ''),
            'customer_document_number' => $this->whenLoaded('customer', fn () => $this->customer?->document_number ?? ''),
            'customer_avatar' => $this->whenLoaded('customer', fn () => $this->customer?->avatar ?? ''),
            'messages' => ConversationMessageResource::collection(
                $this->whenLoaded('messages')
            ),
        ];
    }
}
