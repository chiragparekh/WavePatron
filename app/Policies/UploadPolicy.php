<?php

namespace App\Policies;

use App\Contracts\ChecksSubscriptionAccess;
use App\Enums\AppMode;
use App\Enums\Role;
use App\Models\Upload;
use App\Models\User;

class UploadPolicy
{
    public function create(User $user): bool
    {
        return $user->activeAppMode() === AppMode::Creator;
    }

    public function view(?User $user, Upload $upload): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole(Role::Admin->value)) {
            return true;
        }

        if ($user->id === $upload->user_id) {
            return true;
        }

        if (! $upload->isPublished() || ! $upload->isReady()) {
            return false;
        }

        if ($upload->isFree()) {
            return true;
        }

        return app(ChecksSubscriptionAccess::class)->hasAccess($user, $upload);
    }

    public function update(User $user, Upload $upload): bool
    {
        return $user->id === $upload->user_id
            || $user->hasRole(Role::Admin->value);
    }
}
