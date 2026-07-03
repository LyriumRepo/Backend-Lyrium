<?php

declare(strict_types=1);

namespace App\Catalogs;

final class UserEvents
{
    const CREATED = 'users.created';
    const UPDATED = 'users.updated';
    const DELETED = 'users.deleted';
    const RESTORED = 'users.restored';
    const ROLE_CHANGED = 'users.role.changed';
    const BANNED = 'users.banned';
    const UNBANNED = 'users.unbanned';
    const AVATAR_CHANGED = 'users.avatar.changed';
    const SETTINGS_CHANGED = 'users.settings.changed';
}
