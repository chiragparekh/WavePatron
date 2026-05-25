<?php

namespace App\Support\Subscription;

use App\Contracts\ChecksSubscriptionAccess;
use App\Models\Subscription;
use App\Models\Upload;
use App\Models\User;

class SubscriptionAccessChecker implements ChecksSubscriptionAccess
{
    public function hasAccess(User $listener, Upload $upload): bool
    {
        $creatorProfile = $upload->user?->creatorProfile;

        if ($creatorProfile === null) {
            return false;
        }

        return Subscription::query()
            ->where('user_id', $listener->id)
            ->where('creator_profile_id', $creatorProfile->id)
            ->get()
            ->contains(fn (Subscription $subscription) => $subscription->isAccessible());
    }
}
