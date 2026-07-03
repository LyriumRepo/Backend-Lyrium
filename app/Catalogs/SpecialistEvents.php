<?php

declare(strict_types=1);

namespace App\Catalogs;

final class SpecialistEvents
{
    const CREATED = 'specialists.created';
    const UPDATED = 'specialists.updated';
    const DELETED = 'specialists.deleted';
    const ASSIGNED = 'specialists.assigned';
    const UNASSIGNED = 'specialists.unassigned';
    const SCHEDULE_UPDATED = 'specialists.schedule.updated';
}
