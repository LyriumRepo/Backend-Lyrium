<?php

declare(strict_types=1);

namespace App\Catalogs;

final class TicketEvents
{
    const CREATED = 'tickets.created';
    const STATUS_CHANGED = 'tickets.status.changed';
    const ASSIGNED = 'tickets.assigned';
    const CLOSED = 'tickets.closed';
    const MESSAGE_ADDED = 'tickets.message.added';
    const PRIORITY_CHANGED = 'tickets.priority.changed';
}
