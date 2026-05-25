<?php

namespace App\Listeners;

use App\Actions\Activity\LogAppActivity;
use App\Actions\Payment\RecordPaymentSnapshot;
use App\Actions\Payment\SyncCreatorPayoutStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CreatorProfile;
use App\Models\Subscription;
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeWebhook
{
    public function __construct(
        private RecordPaymentSnapshot $recordPaymentSnapshot,
        private SyncCreatorPayoutStatus $syncCreatorPayoutStatus,
        private LogAppActivity $logAppActivity,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;
        $object = $event->payload['data']['object'] ?? [];

        match ($type) {
            'invoice.payment_succeeded' => $this->recordPaymentSnapshot->fromInvoice($object),
            'customer.subscription.created', 'customer.subscription.updated' => $this->syncSubscription($object),
            'account.updated' => $this->syncConnectAccount($object),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    private function syncSubscription(array $subscription): void
    {
        $stripeId = $subscription['id'] ?? null;

        if (! is_string($stripeId)) {
            return;
        }

        $local = Subscription::query()->where('stripe_id', $stripeId)->first();

        if ($local === null) {
            return;
        }

        $metadata = $subscription['metadata'] ?? [];
        $creatorProfileId = $metadata['creator_profile_id'] ?? null;
        $tierId = $metadata['tier_id'] ?? null;
        $previousStatus = $local->local_status;

        $localStatus = match ($subscription['status'] ?? null) {
            'active', 'trialing' => SubscriptionStatus::Active,
            'canceled' => SubscriptionStatus::Cancelled,
            'past_due', 'unpaid' => SubscriptionStatus::Grace,
            'incomplete', 'incomplete_expired' => SubscriptionStatus::Expired,
            default => $local->local_status,
        };

        $local->forceFill([
            'creator_profile_id' => $creatorProfileId !== null ? (int) $creatorProfileId : $local->creator_profile_id,
            'tier_id' => $tierId !== null ? (int) $tierId : $local->tier_id,
            'local_status' => $localStatus,
        ])->save();

        if ($previousStatus !== $localStatus) {
            $this->logAppActivity->execute(
                event: 'subscription_synced',
                subject: $local,
                properties: [
                    'local_status' => [
                        'from' => $previousStatus->value,
                        'to' => $localStatus->value,
                    ],
                    'stripe_status' => $subscription['status'] ?? null,
                    'stripe_subscription_id' => $stripeId,
                ],
                logName: 'subscription',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function syncConnectAccount(array $account): void
    {
        $accountId = $account['id'] ?? null;

        if (! is_string($accountId)) {
            return;
        }

        $profile = CreatorProfile::query()
            ->where('stripe_connect_account_id', $accountId)
            ->first();

        if ($profile === null) {
            return;
        }

        if ($profile->payoutIsRestricted()) {
            return;
        }

        ($this->syncCreatorPayoutStatus)($profile);
    }
}
