<?php

namespace App\Support\CreatorProfile;

use App\Contracts\ChecksCreatorProfile;
use App\Models\User;

class NullCreatorProfileChecker implements ChecksCreatorProfile
{
    public function hasProfile(User $user): bool
    {
        return false;
    }
}
