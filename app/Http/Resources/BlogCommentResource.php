<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'post_id' => $this->blog_post_id,
            'article_id' => $this->commentable_type === 'article' ? $this->commentable_id : null,
            'video_id' => $this->commentable_type === 'video' ? $this->commentable_id : null,
            'podcast_id' => $this->commentable_type === 'podcast' ? $this->commentable_id : null,
            'short_id' => $this->commentable_type === 'short' ? $this->commentable_id : null,
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'content' => $this->content,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->when($this->relationLoaded('user'), fn () => $this->user ? [
                'id' => $this->user->id,
                'display_name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ] : null),
            'can_edit' => $user && $this->user_id && $user->id === $this->user_id,
            'can_delete' => $user && $this->user_id && $user->id === $this->user_id,
        ];
    }
}
