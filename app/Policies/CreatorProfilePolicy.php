<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CreatorProfile;
use App\Models\User;

class CreatorProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function view(User $user, CreatorProfile $creatorProfile): bool
    {
        return $user->hasRole(Role::Admin->value)
            || $user->id === $creatorProfile->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Creator->value)
            && ! $user->creatorProfile()->exists();
    }

    public function update(User $user, CreatorProfile $creatorProfile): bool
    {
        return $user->id === $creatorProfile->user_id;
    }

    public function delete(User $user, CreatorProfile $creatorProfile): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function restore(User $user, CreatorProfile $creatorProfile): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function forceDelete(User $user, CreatorProfile $creatorProfile): bool
    {
        return $user->hasRole(Role::Admin->value);
    }
}
