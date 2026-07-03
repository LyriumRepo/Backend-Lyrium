<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ForumEvents
{
    const TOPIC_CREATED = 'forum.topic.created';
    const TOPIC_DELETED = 'forum.topic.deleted';
    const POST_CREATED = 'forum.post.created';
    const POST_DELETED = 'forum.post.deleted';
    const POST_REPORTED = 'forum.post.reported';
}
