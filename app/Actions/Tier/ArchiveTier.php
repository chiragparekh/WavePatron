<?php

namespace App\Actions\Tier;

use App\Enums\TierStatus;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ArchiveTier
{
    public function __construct(private LogTierActivity $logTierActivity) {}

    public function execute(Tier $tier, User $actor): Tier
    {
        if (! in_array($tier->status, [TierStatus::Approved, TierStatus::Active], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only approved or active tiers can be archived.',
            ]);
        }

        $previousStatus = $tier->status;

        $tier->update([
            'status' => TierStatus::Archived,
        ]);

        $this->logTierActivity->execute($tier, 'archived', $actor, [
            'status' => [
                'from' => $previousStatus->value,
                'to' => TierStatus::Archived->value,
            ],
        ]);

        return $tier->fresh();
    }
}
