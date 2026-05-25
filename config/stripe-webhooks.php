<?php

use App\Jobs\StripeWebhooks\HandleAccountUpdatedJob;
use App\Jobs\StripeWebhooks\HandleChargeRefundedJob;
use App\Jobs\StripeWebhooks\HandleCheckoutSessionCompletedJob;
use App\Jobs\StripeWebhooks\HandleDisputeCreatedJob;
use App\Jobs\StripeWebhooks\HandleInvoicePaymentFailedJob;
use App\Jobs\StripeWebhooks\HandleInvoicePaymentSucceededJob;
use App\Jobs\StripeWebhooks\HandlePayoutPaidJob;
use App\Jobs\StripeWebhooks\HandleSubscriptionEventJob;
use App\Jobs\StripeWebhooks\HandleTransferCreatedJob;
use App\Models\WebhookCall;
use Spatie\StripeWebhooks\StripeWebhookProfile;

return [
    /*
     * Stripe will sign each webhook using a secret. You can find the used secret at the
     * webhook configuration settings: https://dashboard.stripe.com/account/webhooks.
     */
    'signing_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
     * You can define a default job that should be run for all other Stripe event type
     * without a job defined in next configuration.
     * You may leave it empty to store the job in database but without processing it.
     */
    'default_job' => '',

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Stripe event type with the `.` replaced by a `_`.
     *
     * You can find a list of Stripe webhook types here:
     * https://stripe.com/docs/api#event_types.
     */
    'jobs' => [
        'checkout_session_completed' => HandleCheckoutSessionCompletedJob::class,
        'customer_subscription_created' => HandleSubscriptionEventJob::class,
        'customer_subscription_updated' => HandleSubscriptionEventJob::class,
        'customer_subscription_deleted' => HandleSubscriptionEventJob::class,
        'invoice_payment_succeeded' => HandleInvoicePaymentSucceededJob::class,
        'invoice_payment_failed' => HandleInvoicePaymentFailedJob::class,
        'account_updated' => HandleAccountUpdatedJob::class,
        'transfer_created' => HandleTransferCreatedJob::class,
        'payout_paid' => HandlePayoutPaidJob::class,
        'charge_refunded' => HandleChargeRefundedJob::class,
        'charge_dispute_created' => HandleDisputeCreatedJob::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall.
     */
    'model' => WebhookCall::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => StripeWebhookProfile::class,

    /*
     * Specify a connection and or a queue to process the webhooks
     */
    'connection' => env('STRIPE_WEBHOOK_CONNECTION'),
    'queue' => env('STRIPE_WEBHOOK_QUEUE'),

    /*
     * When disabled, the package will not verify if the signature is valid.
     * This can be handy in local environments.
     */
    'verify_signature' => env('STRIPE_SIGNATURE_VERIFY', true),
];
