<?php

declare(strict_types=1);

namespace App\Catalogs;

final class DisputeEvents
{
    const CREATED = 'disputes.created';
    const MESSAGE_ADDED = 'disputes.message.added';
    const RESOLVED = 'disputes.resolved';
    const CLOSED = 'disputes.closed';
}
