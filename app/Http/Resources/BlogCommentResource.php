<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->blog_post_id,
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'content' => $this->content,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
