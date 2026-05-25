<?php

namespace App\Actions\Tier;

use App\Enums\TierStatus;
use App\Models\CreatorProfile;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SaveTierDraft
{
    public function __construct(private LogTierActivity $logTierActivity) {}

    /**
     * @param  array{name: string, benefits: list<string>, monthly_price_cents: int, annual_price_cents: ?int}  $attributes
     */
    public function execute(
        CreatorProfile $creatorProfile,
        User $actor,
        array $attributes,
        ?Tier $tier = null,
    ): Tier {
        if ($tier !== null && $tier->creator_profile_id !== $creatorProfile->id) {
            throw ValidationException::withMessages([
                'tier' => 'You cannot update this tier.',
            ]);
        }

        if ($tier !== null && ! $tier->isEditableByCreator()) {
            throw ValidationException::withMessages([
                'tier' => 'This tier can no longer be edited.',
            ]);
        }

        $priceChanges = [];

        if ($tier === null) {
            $tier = new Tier([
                'creator_profile_id' => $creatorProfile->id,
                'status' => TierStatus::Draft,
            ]);
        } elseif ($tier->status === TierStatus::Rejected) {
            $tier->status = TierStatus::Draft;
        }

        if ($tier->name !== $attributes['name']) {
            $tier->name = $attributes['name'];
        }

        if ($tier->benefits !== $attributes['benefits']) {
            $tier->benefits = $attributes['benefits'];
        }

        if ($tier->monthly_price_cents !== $attributes['monthly_price_cents']) {
            $priceChanges['monthly_price_cents'] = [
                'from' => $tier->monthly_price_cents,
                'to' => $attributes['monthly_price_cents'],
            ];
            $tier->monthly_price_cents = $attributes['monthly_price_cents'];
        }

        if ($tier->annual_price_cents !== $attributes['annual_price_cents']) {
            $priceChanges['annual_price_cents'] = [
                'from' => $tier->annual_price_cents,
                'to' => $attributes['annual_price_cents'],
            ];
            $tier->annual_price_cents = $attributes['annual_price_cents'];
        }

        $tier->save();

        if ($priceChanges !== []) {
            $this->logTierActivity->execute($tier, 'price_changed', $actor, $priceChanges);
        }

        return $tier->fresh();
    }
}
