<?php

declare(strict_types=1);

namespace App\Catalogs;

final class OperationalRoleEvents
{
    const CREATED = 'operational_roles.created';
    const UPDATED = 'operational_roles.updated';
    const DELETED = 'operational_roles.deleted';
    const TOGGLED = 'operational_roles.toggled';
    const USER_ASSIGNED = 'operational_roles.user.assigned';
    const USER_REMOVED = 'operational_roles.user.removed';
    const TWOFA_REQUIRED = 'operational_roles.2fa.required';
}
