<?php

namespace Database\Factories;

use App\Models\PlatformFeeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformFeeSetting>
 */
class PlatformFeeSettingFactory extends Factory
{
    protected $model = PlatformFeeSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'percentage_fee' => 10,
            'fixed_fee_cents' => 0,
            'currency' => 'usd',
            'effective_at' => now()->subDay(),
        ];
    }
}
