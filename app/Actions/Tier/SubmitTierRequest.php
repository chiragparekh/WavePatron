<?php

namespace App\Actions\Tier;

use App\Enums\TierStatus;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitTierRequest
{
    public function __construct(private LogTierActivity $logTierActivity) {}

    public function execute(Tier $tier, User $actor): Tier
    {
        if (! in_array($tier->status, [TierStatus::Draft, TierStatus::Rejected], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft tiers can be submitted for review.',
            ]);
        }

        $previousStatus = $tier->status;

        $tier->update([
            'status' => TierStatus::Requested,
            'requested_at' => now(),
            'rejected_at' => null,
        ]);

        $this->logTierActivity->execute($tier, 'requested', $actor, [
            'status' => [
                'from' => $previousStatus->value,
                'to' => TierStatus::Requested->value,
            ],
        ]);

        return $tier->fresh();
    }
}
