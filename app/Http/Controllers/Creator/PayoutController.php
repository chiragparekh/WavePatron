<?php

namespace App\Http\Controllers\Creator;

use App\Actions\Payment\SyncCreatorPayoutStatus;
use App\Contracts\ManagesStripeConnect;
use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    public function show(Request $request, SyncCreatorPayoutStatus $syncStatus): Response
    {
        $profile = $this->profileFor($request);

        if ($profile->stripe_connect_account_id !== null && ! $profile->payoutIsEnabled()) {
            $syncStatus($profile);
            $profile->refresh();
        }

        return Inertia::render('creator/payouts/index', [
            'payout' => [
                'status' => $profile->payout_status->value,
                'stripe_connect_account_id' => $profile->stripe_connect_account_id,
                'can_onboard' => ! $profile->payoutIsEnabled(),
            ],
        ]);
    }

    public function store(Request $request, ManagesStripeConnect $connect): RedirectResponse
    {
        $profile = $this->profileFor($request);

        $url = $connect->createOnboardingLink(
            $profile,
            route('creator.payouts.show'),
            route('creator.payouts.show'),
        );

        return redirect()->away($url);
    }

    private function profileFor(Request $request): CreatorProfile
    {
        $profile = $request->user()?->creatorProfile;

        abort_if($profile === null, 404);

        return $profile;
    }
}
