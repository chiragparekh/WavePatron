<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\TierStatus;
use App\Models\Tier;
use App\Models\User;

class TierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function view(User $user, Tier $tier): bool
    {
        return $user->hasRole(Role::Admin->value)
            || $user->id === $tier->creatorProfile->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Creator->value)
            && $user->creatorProfile()->exists();
    }

    public function update(User $user, Tier $tier): bool
    {
        if ($user->hasRole(Role::Admin->value)) {
            return true;
        }

        return $user->id === $tier->creatorProfile->user_id
            && $tier->isEditableByCreator();
    }

    public function delete(User $user, Tier $tier): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function submit(User $user, Tier $tier): bool
    {
        return $user->id === $tier->creatorProfile->user_id
            && in_array($tier->status, [TierStatus::Draft, TierStatus::Rejected], true);
    }

    public function approve(User $user, Tier $tier): bool
    {
        return $user->hasRole(Role::Admin->value)
            && $tier->status === TierStatus::Requested;
    }

    public function reject(User $user, Tier $tier): bool
    {
        return $user->hasRole(Role::Admin->value)
            && $tier->status === TierStatus::Requested;
    }

    public function activate(User $user, Tier $tier): bool
    {
        return $user->id === $tier->creatorProfile->user_id
            && $tier->status === TierStatus::Approved;
    }

    public function archive(User $user, Tier $tier): bool
    {
        if ($user->hasRole(Role::Admin->value)) {
            return in_array($tier->status, [TierStatus::Approved, TierStatus::Active], true);
        }

        return $user->id === $tier->creatorProfile->user_id
            && in_array($tier->status, [TierStatus::Approved, TierStatus::Active], true);
    }
}
