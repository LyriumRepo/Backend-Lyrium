<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

final class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Store $store): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['seller', 'administrator']);
    }

    public function update(User $user, Store $store): bool
    {
        if ($user->hasRole('administrator')) {
            return true;
        }

        if ($store->owner_id === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->hasRole('administrator');
    }

    public function restore(User $user, Store $store): bool
    {
        return $user->hasRole('administrator');
    }

    public function forceDelete(User $user, Store $store): bool
    {
        return $user->hasRole('administrator');
    }
}
