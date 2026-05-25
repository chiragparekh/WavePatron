<?php

namespace App\Support\Stripe;

use App\Actions\Payment\RecordPaymentSnapshot;
use App\Actions\Payment\SyncCreatorPayoutStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CreatorProfile;
use App\Models\Subscription;

class StripeWebhookHandler
{
    public function __construct(
        private CashierWebhookProcessor $cashierWebhookProcessor,
        private RecordPaymentSnapshot $recordPaymentSnapshot,
        private SyncCreatorPayoutStatus $syncCreatorPayoutStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventType, array $payload): void
    {
        $object = $payload['data']['object'] ?? [];

        match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($object),
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->handleSubscriptionEvent($payload),
            'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($payload, $object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($payload, $object),
            'account.updated' => $this->handleAccountUpdated($object),
            'transfer.created' => null,
            'payout.paid' => null,
            'charge.refunded' => null,
            'charge.dispute.created' => null,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function handleCheckoutSessionCompleted(array $session): void
    {
        // Checkout completion is reconciled through subscription and invoice events.
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionEvent(array $payload): void
    {
        $this->cashierWebhookProcessor->process($payload);

        $subscription = $payload['data']['object'] ?? [];

        if (! is_array($subscription)) {
            return;
        }

        $this->syncSubscriptionMetadata($subscription);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $invoice
     */
    private function handleInvoicePaymentSucceeded(array $payload, array $invoice): void
    {
        $this->cashierWebhookProcessor->process($payload);
        $this->recordPaymentSnapshot->fromInvoice($invoice);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $invoice
     */
    private function handleInvoicePaymentFailed(array $payload, array $invoice): void
    {
        $this->cashierWebhookProcessor->process($payload);

        $stripeSubscriptionId = $invoice['subscription'] ?? null;

        if (! is_string($stripeSubscriptionId)) {
            return;
        }

        $local = Subscription::query()->where('stripe_id', $stripeSubscriptionId)->first();

        if ($local === null) {
            return;
        }

        $local->forceFill([
            'local_status' => SubscriptionStatus::Grace,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    private function syncSubscriptionMetadata(array $subscription): void
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
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function handleAccountUpdated(array $account): void
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
