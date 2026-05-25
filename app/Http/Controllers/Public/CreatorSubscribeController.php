<?php

namespace App\Http\Controllers\Public;

use App\Actions\Payment\CreateCreatorSubscriptionCheckout;
use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use App\Models\Tier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreatorSubscribeController extends Controller
{
    public function __invoke(
        Request $request,
        CreatorProfile $profile,
        Tier $tier,
        CreateCreatorSubscriptionCheckout $createCheckout,
    ): RedirectResponse {
        abort_unless($profile->isPubliclyVisible(), 404);
        abort_unless($tier->creator_profile_id === $profile->id, 404);

        return $createCheckout($request->user(), $profile, $tier);
    }
}
