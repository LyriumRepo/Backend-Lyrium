<?php

declare(strict_types=1);

namespace App\Catalogs;

final class StoreEvents
{
    const CREATED = 'stores.created';
    const UPDATED = 'stores.updated';
    const DELETED = 'stores.deleted';
    const RESTORED = 'stores.restored';
    const APPROVED = 'stores.approved';
    const REJECTED = 'stores.rejected';
    const SUSPENDED = 'stores.suspended';
    const BANNED = 'stores.banned';
    const PROFILE_REQUESTED = 'stores.profile.requested';
    const PROFILE_APPROVED = 'stores.profile.approved';
    const PROFILE_REJECTED = 'stores.profile.rejected';
    const MEMBER_ADDED = 'stores.member.added';
    const MEMBER_REMOVED = 'stores.member.removed';
    const MEMBER_ROLE_CHANGED = 'stores.member.role.changed';
    const BRANCH_CREATED = 'stores.branch.created';
    const BRANCH_UPDATED = 'stores.branch.updated';
    const BRANCH_DELETED = 'stores.branch.deleted';
    const MEDIA_UPLOADED = 'stores.media.uploaded';
    const MEDIA_DELETED = 'stores.media.deleted';
}
