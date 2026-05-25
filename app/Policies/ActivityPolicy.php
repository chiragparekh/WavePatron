<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasRole(Role::Admin->value);
    }
}
