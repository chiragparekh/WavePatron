<?php

namespace App\Actions\Tier;

use App\Contracts\CreatesStripeTierProduct;
use App\Enums\TierStatus;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApproveTier
{
    public function __construct(
        private CreatesStripeTierProduct $stripeTierProductCreator,
        private LogTierActivity $logTierActivity,
    ) {}

    public function execute(Tier $tier, User $actor): Tier
    {
        if ($tier->status !== TierStatus::Requested) {
            throw ValidationException::withMessages([
                'status' => 'Only requested tiers can be approved.',
            ]);
        }

        $stripeReferences = $this->stripeTierProductCreator->create($tier);

        $tier->update([
            'status' => TierStatus::Approved,
            'stripe_product_id' => $stripeReferences['product_id'],
            'stripe_monthly_price_id' => $stripeReferences['price_id_monthly'],
            'stripe_annual_price_id' => $stripeReferences['price_id_annual'],
            'approved_at' => now(),
            'rejected_at' => null,
        ]);

        $this->logTierActivity->execute($tier, 'approved', $actor, [
            'status' => [
                'from' => TierStatus::Requested->value,
                'to' => TierStatus::Approved->value,
            ],
            'stripe_product_id' => $stripeReferences['product_id'],
            'stripe_monthly_price_id' => $stripeReferences['price_id_monthly'],
            'stripe_annual_price_id' => $stripeReferences['price_id_annual'],
        ]);

        return $tier->fresh();
    }
}
