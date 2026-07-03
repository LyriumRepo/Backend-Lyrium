<?php

declare(strict_types=1);

namespace App\Catalogs;

final class PlanEvents
{
    const CREATED = 'plans.created';
    const UPDATED = 'plans.updated';
    const DELETED = 'plans.deleted';
    const STATUS_CHANGED = 'plans.status.changed';
    const REQUEST_CREATED = 'plans.request.created';
    const REQUEST_APPROVED = 'plans.request.approved';
    const REQUEST_REJECTED = 'plans.request.rejected';
}
