<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ForumPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic_id' => $this->forum_topic_id,
            'author_name' => $this->authorName(),
            'content' => $this->content,
            'created' => $this->created_at?->toIso8601String(),
            'reply_to' => $this->reply_to_id,
            'reply_to_name' => $this->when($this->reply_to_id, function () {
                $parent = $this->relationLoaded('replyTo') ? $this->replyTo : null;

                return $parent?->authorName();
            }),
            'reply_to_content' => $this->when($this->reply_to_id, function () {
                $parent = $this->relationLoaded('replyTo') ? $this->replyTo : null;

                return $parent?->content;
            }),
            'votes_up' => $this->likes_count,
            'votes_down' => $this->angry_count,
        ];
    }

    public function authorName(): string
    {
        if ($this->user_id && $this->relationLoaded('user') && $this->user) {
            return $this->user->name;
        }

        return $this->anonymous_name ?? 'Anónimo';
    }
}
