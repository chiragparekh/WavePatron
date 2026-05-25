<?php

namespace App\Support\Stripe;

use App\Contracts\ManagesStripeConnect;
use App\Models\CreatorProfile;
use Illuminate\Support\Str;

class FakeStripeConnectService implements ManagesStripeConnect
{
    /** @var array<string, array{charges_enabled: bool, details_submitted: bool, payouts_enabled: bool}> */
    public static array $accounts = [];

    public function createAccount(CreatorProfile $profile): string
    {
        if ($profile->stripe_connect_account_id !== null) {
            return $profile->stripe_connect_account_id;
        }

        $accountId = 'acct_'.Str::lower(Str::random(14));

        self::$accounts[$accountId] = [
            'charges_enabled' => false,
            'details_submitted' => false,
            'payouts_enabled' => false,
        ];

        $profile->forceFill([
            'stripe_connect_account_id' => $accountId,
            'payout_status' => 'pending',
        ])->save();

        return $accountId;
    }

    public function createOnboardingLink(CreatorProfile $profile, string $returnUrl, string $refreshUrl): string
    {
        $accountId = $this->createAccount($profile);

        return 'https://connect.stripe.test/onboarding/'.$accountId;
    }

    /**
     * @return array{charges_enabled: bool, details_submitted: bool, payouts_enabled: bool}
     */
    public function fetchAccountCapabilities(string $accountId): array
    {
        return self::$accounts[$accountId] ?? [
            'charges_enabled' => false,
            'details_submitted' => false,
            'payouts_enabled' => false,
        ];
    }

    public static function enableAccount(string $accountId): void
    {
        self::$accounts[$accountId] = [
            'charges_enabled' => true,
            'details_submitted' => true,
            'payouts_enabled' => true,
        ];
    }
}
