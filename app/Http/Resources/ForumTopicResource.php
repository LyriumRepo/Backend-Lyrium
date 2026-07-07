<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ForumTopicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'image' => $this->image,
            'created' => $this->created_at?->toIso8601String(),
            'author_name' => $this->resolveAuthorName(),
            'user_id' => $this->user_id,
            'forum_id' => $this->forum_category_id,
            'forum_name' => $this->whenLoaded('category', fn () => $this->category->name, 'General'),
            'reply_count' => $this->reply_count,
            'views' => $this->views,
            'votes_up' => $this->total_reactions,
            'votes_down' => 0,
            'slug' => "tema-{$this->id}",
        ];
    }

    private function resolveAuthorName(): string
    {
        if ($this->user_id && $this->relationLoaded('user') && $this->user) {
            return $this->user->name;
        }

        return $this->anonymous_name ?? 'Anónimo';
    }
}
