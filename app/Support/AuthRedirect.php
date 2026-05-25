<?php

namespace App\Support;

use App\Contracts\ChecksCreatorProfile;
use App\Enums\AppMode;
use App\Enums\Role;
use App\Models\User;

class AuthRedirect
{
    public function __construct(private ChecksCreatorProfile $creatorProfiles) {}

    public function homeUrl(User $user): string
    {
        if ($user->hasRole(Role::Admin->value)) {
            return '/admin';
        }

        return $this->dashboardUrlFor($user);
    }

    public function dashboardUrlFor(User $user): string
    {
        if ($user->activeAppMode() === AppMode::Creator) {
            if (! $this->creatorProfiles->hasProfile($user)) {
                return route('creator.onboarding', absolute: false);
            }
        }

        return route('dashboard', absolute: false);
    }
}
