<?php

namespace App\Actions\Payment;

use App\Actions\Activity\LogAppActivity;
use App\Contracts\ManagesStripeConnect;
use App\Enums\CreatorPayoutStatus;
use App\Models\CreatorProfile;

class SyncCreatorPayoutStatus
{
    public function __construct(
        private ManagesStripeConnect $connect,
        private LogAppActivity $logAppActivity,
    ) {}

    public function __invoke(CreatorProfile $profile): CreatorProfile
    {
        if ($profile->stripe_connect_account_id === null) {
            return $profile;
        }

        $previousStatus = $profile->payout_status;

        $capabilities = $this->connect->fetchAccountCapabilities($profile->stripe_connect_account_id);

        $status = match (true) {
            $capabilities['charges_enabled'] && $capabilities['payouts_enabled'] => CreatorPayoutStatus::Enabled,
            $capabilities['details_submitted'], $profile->stripe_connect_account_id !== null => CreatorPayoutStatus::Pending,
            default => CreatorPayoutStatus::NotStarted,
        };

        if ($profile->payoutIsRestricted()) {
            $status = CreatorPayoutStatus::Restricted;
        }

        $profile->forceFill(['payout_status' => $status])->save();

        if ($previousStatus !== $status) {
            $this->logAppActivity->execute(
                event: 'payout_status_changed',
                subject: $profile,
                properties: [
                    'payout_status' => [
                        'from' => $previousStatus->value,
                        'to' => $status->value,
                    ],
                ],
                logName: 'payout',
            );
        }

        return $profile->refresh();
    }
}
