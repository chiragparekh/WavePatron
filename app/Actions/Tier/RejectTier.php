<?php

namespace App\Actions\Tier;

use App\Enums\TierStatus;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RejectTier
{
    public function __construct(private LogTierActivity $logTierActivity) {}

    public function execute(Tier $tier, User $actor): Tier
    {
        if ($tier->status !== TierStatus::Requested) {
            throw ValidationException::withMessages([
                'status' => 'Only requested tiers can be rejected.',
            ]);
        }

        $tier->update([
            'status' => TierStatus::Rejected,
            'rejected_at' => now(),
        ]);

        $this->logTierActivity->execute($tier, 'rejected', $actor, [
            'status' => [
                'from' => TierStatus::Requested->value,
                'to' => TierStatus::Rejected->value,
            ],
        ]);

        return $tier->fresh();
    }
}
