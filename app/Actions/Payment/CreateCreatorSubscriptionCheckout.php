<?php

namespace App\Actions\Payment;

use App\Models\CreatorProfile;
use App\Models\Tier;
use App\Models\User;
use App\Support\PlatformFee\PlatformFeeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CreateCreatorSubscriptionCheckout
{
    public function __construct(private PlatformFeeCalculator $feeCalculator) {}

    public function __invoke(User $listener, CreatorProfile $profile, Tier $tier): RedirectResponse
    {
        if ($tier->creator_profile_id !== $profile->id) {
            abort(404);
        }

        if (! $tier->isSubscribable()) {
            throw ValidationException::withMessages([
                'tier' => 'This tier is not available for subscription yet.',
            ]);
        }

        if ($listener->subscribed($this->subscriptionName($profile))) {
            throw ValidationException::withMessages([
                'tier' => 'You already have an active subscription to this creator.',
            ]);
        }

        $fees = $this->feeCalculator->calculate($profile, $tier->monthly_price_cents);

        $checkout = $listener
            ->newSubscription($this->subscriptionName($profile), $tier->stripe_monthly_price_id)
            ->checkout([
                'success_url' => route('creators.show', $profile->handle).'?subscribed=1',
                'cancel_url' => route('creators.show', $profile->handle).'?checkout=cancelled',
            ], [
                'subscription_data' => [
                    'application_fee_percent' => $fees['percentage_fee'],
                    'transfer_data' => [
                        'destination' => $profile->stripe_connect_account_id,
                    ],
                    'metadata' => [
                        'creator_profile_id' => (string) $profile->id,
                        'tier_id' => (string) $tier->id,
                        'listener_user_id' => (string) $listener->id,
                    ],
                ],
            ]);

        return $checkout;
    }

    public function subscriptionName(CreatorProfile $profile): string
    {
        return 'creator-'.$profile->id;
    }
}
