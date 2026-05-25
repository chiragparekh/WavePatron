<?php

namespace App\Support\Stripe;

use App\Contracts\ManagesStripeConnect;
use App\Models\CreatorProfile;
use Laravel\Cashier\Cashier;

class StripeConnectService implements ManagesStripeConnect
{
    public function createAccount(CreatorProfile $profile): string
    {
        if ($profile->stripe_connect_account_id !== null) {
            return $profile->stripe_connect_account_id;
        }

        $account = Cashier::stripe()->accounts->create([
            'type' => 'express',
            'email' => $profile->support_email ?? $profile->user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'metadata' => [
                'creator_profile_id' => (string) $profile->id,
                'handle' => $profile->handle,
            ],
        ]);

        $profile->forceFill([
            'stripe_connect_account_id' => $account->id,
            'payout_status' => 'pending',
        ])->save();

        return $account->id;
    }

    public function createOnboardingLink(CreatorProfile $profile, string $returnUrl, string $refreshUrl): string
    {
        $accountId = $this->createAccount($profile);

        $link = Cashier::stripe()->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    /**
     * @return array{charges_enabled: bool, details_submitted: bool, payouts_enabled: bool}
     */
    public function fetchAccountCapabilities(string $accountId): array
    {
        $account = Cashier::stripe()->accounts->retrieve($accountId);

        return [
            'charges_enabled' => (bool) $account->charges_enabled,
            'details_submitted' => (bool) $account->details_submitted,
            'payouts_enabled' => (bool) $account->payouts_enabled,
        ];
    }
}
