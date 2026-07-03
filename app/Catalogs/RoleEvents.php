<?php

declare(strict_types=1);

namespace App\Catalogs;

final class RoleEvents
{
    const CREATED = 'roles.created';
    const UPDATED = 'roles.updated';
    const DELETED = 'roles.deleted';
    const PERMISSIONS_ASSIGNED = 'roles.permissions.assigned';
    const PERMISSIONS_REVOKED = 'roles.permissions.revoked';
    const USERS_ASSIGNED = 'roles.users.assigned';
    const USERS_REVOKED = 'roles.users.revoked';
}
