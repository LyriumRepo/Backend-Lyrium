<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ServiceEvents
{
    const CREATED = 'services.created';
    const UPDATED = 'services.updated';
    const DELETED = 'services.deleted';
    const STATUS_CHANGED = 'services.status.changed';
    const PRICE_CHANGED = 'services.price.changed';
    const SCHEDULE_UPDATED = 'services.schedule.updated';
    const MEDIA_UPLOADED = 'services.media.uploaded';
    const MEDIA_DELETED = 'services.media.deleted';
}
