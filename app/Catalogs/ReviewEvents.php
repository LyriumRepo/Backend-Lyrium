<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ReviewEvents
{
    const CREATED = 'reviews.created';
    const UPDATED = 'reviews.updated';
    const DELETED = 'reviews.deleted';
    const MODERATED = 'reviews.moderated';
    const REPORTED = 'reviews.reported';
}
