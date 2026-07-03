<?php

declare(strict_types=1);

namespace App\Catalogs;

final class BlogEvents
{
    const POST_CREATED = 'blog.post.created';
    const POST_UPDATED = 'blog.post.updated';
    const POST_DELETED = 'blog.post.deleted';
    const POST_STATUS_CHANGED = 'blog.post.status.changed';
    const COMMENT_CREATED = 'blog.comment.created';
    const COMMENT_DELETED = 'blog.comment.deleted';
}
