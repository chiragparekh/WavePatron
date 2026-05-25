<?php

namespace Database\Factories;

use App\Enums\CreatorPayoutStatus;
use App\Enums\CreatorProfileVisibility;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CreatorProfile>
 */
class CreatorProfileFactory extends Factory
{
    protected $model = CreatorProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $handle = Str::slug(fake()->unique()->userName());

        return [
            'user_id' => User::factory()->creator(),
            'handle' => $handle,
            'display_name' => fake()->name(),
            'tagline' => fake()->optional()->sentence(),
            'bio' => fake()->optional()->paragraph(),
            'avatar_path' => null,
            'cover_image_path' => null,
            'categories' => fake()->optional()->randomElements(
                ['music', 'podcast', 'audiobook', 'education', 'comedy'],
                fake()->numberBetween(1, 3),
            ),
            'website' => fake()->optional()->url(),
            'social_links' => fake()->optional()->passthrough([
                'x' => fake()->url(),
                'instagram' => fake()->url(),
            ]),
            'support_email' => fake()->optional()->safeEmail(),
            'visibility' => CreatorProfileVisibility::Public,
            'payout_status' => CreatorPayoutStatus::NotStarted,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => CreatorProfileVisibility::Public,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => CreatorProfileVisibility::Hidden,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => CreatorProfileVisibility::Draft,
        ]);
    }

    public function payoutEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_connect_account_id' => 'acct_'.fake()->regexify('[A-Za-z0-9]{16}'),
            'payout_status' => CreatorPayoutStatus::Enabled,
        ]);
    }

    public function payoutPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_connect_account_id' => 'acct_'.fake()->regexify('[A-Za-z0-9]{16}'),
            'payout_status' => CreatorPayoutStatus::Pending,
        ]);
    }
}
