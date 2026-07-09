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
            'user_id' => $this->user_id,
            'author_name' => $this->authorName(),
            'content' => $this->content,
            'created' => $this->created_at?->toIso8601String(),
            'reply_to' => $this->reply_to_id,
            'reply_to_name' => $this->when($this->reply_to_id, function () {
                $parent = $this->relationLoaded('replyTo') ? $this->replyTo : null;
                if (!$parent) return null;
                return self::resolveAuthorName($parent);
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
        return self::resolveAuthorName($this->resource);
    }

    public static function resolveAuthorName(\App\Models\ForumPost $post): string
    {
        if ($post->user_id && $post->relationLoaded('user') && $post->user) {
            return $post->user->name;
        }
        return $post->anonymous_name ?? 'Anónimo';
    }
}
