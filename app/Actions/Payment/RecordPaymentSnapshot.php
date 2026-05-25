<?php

namespace App\Actions\Payment;

use App\Actions\Activity\LogAppActivity;
use App\Enums\PaymentStatus;
use App\Models\CreatorProfile;
use App\Models\PaymentSnapshot;
use App\Models\Subscription;
use App\Models\Tier;
use App\Models\User;
use App\Support\PlatformFee\PlatformFeeCalculator;
use Illuminate\Support\Carbon;

class RecordPaymentSnapshot
{
    public function __construct(
        private PlatformFeeCalculator $feeCalculator,
        private LogAppActivity $logAppActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $invoice
     */
    public function fromInvoice(array $invoice): ?PaymentSnapshot
    {
        $invoiceId = $invoice['id'] ?? null;

        if (! is_string($invoiceId) || $invoiceId === '') {
            return null;
        }

        if (PaymentSnapshot::query()->where('stripe_invoice_id', $invoiceId)->exists()) {
            return PaymentSnapshot::query()->where('stripe_invoice_id', $invoiceId)->first();
        }

        $metadata = $invoice['subscription_details']['metadata']
            ?? $invoice['metadata']
            ?? [];

        $creatorProfileId = (int) ($metadata['creator_profile_id'] ?? 0);
        $tierId = (int) ($metadata['tier_id'] ?? 0);
        $listenerUserId = (int) ($metadata['listener_user_id'] ?? ($invoice['customer_email'] ?? 0));

        if ($creatorProfileId === 0 || $tierId === 0) {
            $subscription = $this->resolveSubscription($invoice['subscription'] ?? null);

            if ($subscription === null) {
                return null;
            }

            $creatorProfileId = (int) $subscription->creator_profile_id;
            $tierId = (int) $subscription->tier_id;
            $listenerUserId = (int) $subscription->user_id;
        }

        $profile = CreatorProfile::query()->find($creatorProfileId);
        $tier = Tier::query()->find($tierId);
        $listener = User::query()->find($listenerUserId);

        if ($profile === null || $tier === null || $listener === null) {
            return null;
        }

        $grossAmountCents = (int) ($invoice['amount_paid'] ?? $invoice['total'] ?? 0);
        $stripeFeeCents = $this->resolveStripeFeeCents($invoice);
        $fees = $this->feeCalculator->calculate($profile, $grossAmountCents, $stripeFeeCents);

        $subscription = $this->resolveSubscription($invoice['subscription'] ?? null);

        $snapshot = PaymentSnapshot::query()->create([
            'user_id' => $listener->id,
            'creator_profile_id' => $profile->id,
            'tier_id' => $tier->id,
            'subscription_id' => $subscription?->id,
            'gross_amount_cents' => $grossAmountCents,
            'stripe_fee_cents' => $stripeFeeCents,
            'platform_fee_cents' => $fees['platform_fee_cents'],
            'creator_payout_cents' => $fees['creator_payout_cents'],
            'currency' => (string) ($invoice['currency'] ?? $fees['currency']),
            'stripe_payment_intent_id' => is_string($invoice['payment_intent'] ?? null) ? $invoice['payment_intent'] : null,
            'stripe_charge_id' => is_string($invoice['charge'] ?? null) ? $invoice['charge'] : null,
            'stripe_invoice_id' => $invoiceId,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => isset($invoice['status_transitions']['paid_at'])
                ? Carbon::createFromTimestamp((int) $invoice['status_transitions']['paid_at'])
                : now(),
        ]);

        $this->logAppActivity->execute(
            event: 'payment_recorded',
            subject: $snapshot,
            causer: $listener,
            properties: [
                'gross_amount_cents' => $grossAmountCents,
                'platform_fee_cents' => $fees['platform_fee_cents'],
                'creator_payout_cents' => $fees['creator_payout_cents'],
                'stripe_invoice_id' => $invoiceId,
            ],
            logName: 'payment',
        );

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function resolveStripeFeeCents(array $invoice): ?int
    {
        $balanceTransaction = $invoice['charge']['balance_transaction']['fee'] ?? null;

        if (is_int($balanceTransaction)) {
            return $balanceTransaction;
        }

        return null;
    }

    private function resolveSubscription(mixed $stripeSubscriptionId): ?Subscription
    {
        if (! is_string($stripeSubscriptionId) || $stripeSubscriptionId === '') {
            return null;
        }

        return Subscription::query()->where('stripe_id', $stripeSubscriptionId)->first();
    }
}
