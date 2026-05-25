<?php

namespace App\Contracts;

use App\Models\Upload;
use App\Models\User;

interface ChecksSubscriptionAccess
{
    public function hasAccess(User $listener, Upload $upload): bool;
}
