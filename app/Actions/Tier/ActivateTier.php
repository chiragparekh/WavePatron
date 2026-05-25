<?php

namespace App\Actions\Tier;

use App\Enums\TierStatus;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ActivateTier
{
    public function __construct(private LogTierActivity $logTierActivity) {}

    public function execute(Tier $tier, User $actor): Tier
    {
        if ($tier->status !== TierStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Only approved tiers can be activated.',
            ]);
        }

        $tier->update([
            'status' => TierStatus::Active,
        ]);

        $this->logTierActivity->execute($tier, 'activated', $actor, [
            'status' => [
                'from' => TierStatus::Approved->value,
                'to' => TierStatus::Active->value,
            ],
        ]);

        return $tier->fresh();
    }
}
