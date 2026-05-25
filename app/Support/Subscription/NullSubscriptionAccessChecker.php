<?php

namespace App\Support\Subscription;

use App\Contracts\ChecksSubscriptionAccess;
use App\Models\Upload;
use App\Models\User;

class NullSubscriptionAccessChecker implements ChecksSubscriptionAccess
{
    public function hasAccess(User $listener, Upload $upload): bool
    {
        return false;
    }
}
