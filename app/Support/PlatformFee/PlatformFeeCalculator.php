<?php

namespace App\Support\PlatformFee;

use App\Models\CreatorFeeOverride;
use App\Models\CreatorProfile;
use App\Models\PlatformFeeSetting;

class PlatformFeeCalculator
{
    /**
     * @return array{
     *     percentage_fee: float,
     *     fixed_fee_cents: int,
     *     platform_fee_cents: int,
     *     creator_payout_cents: int,
     *     currency: string
     * }
     */
    public function calculate(CreatorProfile $profile, int $grossAmountCents, ?int $stripeFeeCents = null): array
    {
        $currency = (string) config('cashier.currency', 'usd');
        $override = CreatorFeeOverride::currentFor($profile, $currency);
        $setting = PlatformFeeSetting::current($currency);

        $percentageFee = (float) ($override?->percentage_fee ?? $setting?->percentage_fee ?? 10);
        $fixedFeeCents = (int) ($override?->fixed_fee_cents ?? $setting?->fixed_fee_cents ?? 0);

        $percentageComponent = (int) round($grossAmountCents * ($percentageFee / 100));
        $platformFeeCents = $percentageComponent + $fixedFeeCents;

        if ($stripeFeeCents !== null) {
            $platformFeeCents = max($platformFeeCents, 0);
        }

        $platformFeeCents = min($platformFeeCents, $grossAmountCents);
        $creatorPayoutCents = max($grossAmountCents - $platformFeeCents, 0);

        return [
            'percentage_fee' => $percentageFee,
            'fixed_fee_cents' => $fixedFeeCents,
            'platform_fee_cents' => $platformFeeCents,
            'creator_payout_cents' => $creatorPayoutCents,
            'currency' => $currency,
        ];
    }
}
