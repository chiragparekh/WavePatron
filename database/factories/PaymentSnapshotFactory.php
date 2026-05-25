<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\CreatorProfile;
use App\Models\PaymentSnapshot;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentSnapshot>
 */
class PaymentSnapshotFactory extends Factory
{
    protected $model = PaymentSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gross = fake()->numberBetween(500, 5000);
        $platformFee = (int) round($gross * 0.1);

        return [
            'user_id' => User::factory()->listener(),
            'creator_profile_id' => CreatorProfile::factory(),
            'tier_id' => Tier::factory()->active(),
            'subscription_id' => null,
            'gross_amount_cents' => $gross,
            'stripe_fee_cents' => (int) round($gross * 0.029),
            'platform_fee_cents' => $platformFee,
            'creator_payout_cents' => $gross - $platformFee,
            'currency' => 'usd',
            'stripe_payment_intent_id' => 'pi_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_charge_id' => 'ch_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_invoice_id' => 'in_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ];
    }
}
