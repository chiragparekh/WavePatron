<?php

namespace Database\Factories;

use App\Models\CreatorFeeOverride;
use App\Models\CreatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorFeeOverride>
 */
class CreatorFeeOverrideFactory extends Factory
{
    protected $model = CreatorFeeOverride::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'percentage_fee' => 5,
            'fixed_fee_cents' => 50,
            'currency' => 'usd',
            'effective_at' => now()->subDay(),
        ];
    }
}
