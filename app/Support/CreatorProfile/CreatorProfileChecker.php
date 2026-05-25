<?php

namespace App\Support\CreatorProfile;

use App\Contracts\ChecksCreatorProfile;
use App\Models\User;

class CreatorProfileChecker implements ChecksCreatorProfile
{
    public function hasProfile(User $user): bool
    {
        return $user->creatorProfile()->exists();
    }
}
