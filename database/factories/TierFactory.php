<?php

namespace Database\Factories;

use App\Enums\TierStatus;
use App\Models\CreatorProfile;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tier>
 */
class TierFactory extends Factory
{
    protected $model = Tier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'name' => fake()->words(2, true),
            'benefits' => fake()->sentences(3),
            'monthly_price_cents' => fake()->numberBetween(500, 2500),
            'annual_price_cents' => null,
            'status' => TierStatus::Draft,
            'stripe_product_id' => null,
            'stripe_monthly_price_id' => null,
            'stripe_annual_price_id' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TierStatus::Draft,
        ]);
    }

    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TierStatus::Requested,
            'requested_at' => now(),
        ]);
    }

    public function approved(): static
    {
        $suffix = Str::lower(Str::random(10));

        return $this->state(fn (array $attributes) => [
            'status' => TierStatus::Approved,
            'stripe_product_id' => 'prod_'.$suffix,
            'stripe_monthly_price_id' => 'price_monthly_'.$suffix,
            'stripe_annual_price_id' => fake()->optional()->passthrough('price_annual_'.$suffix),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TierStatus::Rejected,
            'rejected_at' => now(),
        ]);
    }

    public function active(): static
    {
        $suffix = Str::lower(Str::random(10));

        return $this->state(fn (array $attributes) => [
            'status' => TierStatus::Active,
            'stripe_product_id' => 'prod_'.$suffix,
            'stripe_monthly_price_id' => 'price_monthly_'.$suffix,
            'approved_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TierStatus::Archived,
        ]);
    }
}
