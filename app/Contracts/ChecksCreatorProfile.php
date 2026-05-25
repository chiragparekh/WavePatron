<?php

namespace App\Contracts;

use App\Models\User;

interface ChecksCreatorProfile
{
    public function hasProfile(User $user): bool;
}
