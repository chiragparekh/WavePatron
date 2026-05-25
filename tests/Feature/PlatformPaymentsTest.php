<?php

use App\Actions\Payment\RecordPaymentSnapshot;
use App\Actions\Payment\SyncCreatorPayoutStatus;
use App\Actions\Webhook\LogWebhookActivity;
use App\Contracts\ChecksSubscriptionAccess;
use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Enums\CreatorPayoutStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\StripeWebhooks\HandleInvoicePaymentSucceededJob;
use App\Models\CreatorFeeOverride;
use App\Models\CreatorProfile;
use App\Models\PaymentSnapshot;
use App\Models\PlatformFeeSetting;
use App\Models\Subscription;
use App\Models\Tier;
use App\Models\Upload;
use App\Models\User;
use App\Models\WebhookCall;
use App\Support\PlatformFee\PlatformFeeCalculator;
use App\Support\Stripe\FakeStripeConnectService;
use App\Support\Stripe\StripeWebhookHandler;
use App\Support\Subscription\SubscriptionAccessChecker;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('platform fee calculator applies global settings and creator overrides', function () {
    $profile = CreatorProfile::factory()->create();

    PlatformFeeSetting::factory()->create([
        'percentage_fee' => 10,
        'fixed_fee_cents' => 0,
        'currency' => 'usd',
        'effective_at' => now()->subDay(),
    ]);

    CreatorFeeOverride::factory()->for($profile)->create([
        'percentage_fee' => 5,
        'fixed_fee_cents' => 25,
        'currency' => 'usd',
        'effective_at' => now()->subDay(),
    ]);

    $result = app(PlatformFeeCalculator::class)->calculate($profile, 1000);

    expect($result['platform_fee_cents'])->toBe(75)
        ->and($result['creator_payout_cents'])->toBe(925);
});

test('listeners cannot subscribe when creator payouts are not enabled', function () {
    $listener = User::factory()->listener()->create();
    $profile = CreatorProfile::factory()->public()->payoutPending()->create();
    $tier = Tier::factory()->for($profile)->active()->create();

    $this->actingAs($listener)
        ->get(route('creators.subscribe', ['profile' => $profile->handle, 'tier' => $tier]))
        ->assertSessionHasErrors('tier');
});

test('creators can start stripe connect onboarding from the payouts page', function () {
    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->create();

    $this->actingAs($creator)
        ->post(route('creator.payouts.onboarding'))
        ->assertRedirect('https://connect.stripe.test/onboarding/'.$profile->fresh()->stripe_connect_account_id);

    expect($profile->fresh())
        ->stripe_connect_account_id->not->toBeNull()
        ->payout_status->toBe(CreatorPayoutStatus::Pending);
});

test('creators can view payout status page', function () {
    $creator = User::factory()->creator()->create();
    CreatorProfile::factory()->for($creator)->payoutEnabled()->create();

    $this->actingAs($creator)
        ->get(route('creator.payouts.show'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('creator/payouts/index')
            ->where('payout.status', CreatorPayoutStatus::Enabled->value)
            ->where('payout.can_onboard', false)
        );
});

test('subscription access checker grants premium access for active creator subscriptions', function () {
    Storage::fake('local');

    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->payoutEnabled()->create();
    $listener = User::factory()->listener()->create();
    $tier = Tier::factory()->for($profile)->active()->create();

    $upload = Upload::factory()->for($creator)->ready()->create([
        'publish_status' => AudioPublishStatus::Published,
        'access_level' => AudioAccessLevel::Premium,
    ]);

    Subscription::query()->create([
        'user_id' => $listener->id,
        'type' => 'creator-'.$profile->id,
        'stripe_id' => 'sub_access_test',
        'stripe_status' => 'active',
        'stripe_price' => $tier->stripe_monthly_price_id,
        'creator_profile_id' => $profile->id,
        'tier_id' => $tier->id,
        'local_status' => SubscriptionStatus::Active,
    ]);

    expect(app(ChecksSubscriptionAccess::class)->hasAccess($listener, $upload))->toBeTrue();

    $this->actingAs($listener)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertSuccessful();
});

test('subscription access checker honors grace period for canceled subscriptions', function () {
    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->create();
    $listener = User::factory()->listener()->create();
    $upload = Upload::factory()->for($creator)->ready()->create([
        'publish_status' => AudioPublishStatus::Published,
        'access_level' => AudioAccessLevel::Premium,
    ]);

    Subscription::query()->create([
        'user_id' => $listener->id,
        'type' => 'creator-'.$profile->id,
        'stripe_id' => 'sub_grace_test',
        'stripe_status' => 'canceled',
        'stripe_price' => 'price_test',
        'creator_profile_id' => $profile->id,
        'ends_at' => now()->addWeek(),
        'local_status' => SubscriptionStatus::Cancelled,
    ]);

    expect(app(SubscriptionAccessChecker::class)->hasAccess($listener, $upload))->toBeTrue();
});

test('payment snapshots store fee breakdown and remain idempotent', function () {
    $profile = CreatorProfile::factory()->create();
    $tier = Tier::factory()->for($profile)->active()->create();
    $listener = User::factory()->listener()->create();

    PlatformFeeSetting::factory()->create([
        'percentage_fee' => 10,
        'fixed_fee_cents' => 0,
        'currency' => 'usd',
        'effective_at' => now()->subDay(),
    ]);

    $invoice = [
        'id' => 'in_test_snapshot',
        'amount_paid' => 1000,
        'currency' => 'usd',
        'metadata' => [
            'creator_profile_id' => (string) $profile->id,
            'tier_id' => (string) $tier->id,
            'listener_user_id' => (string) $listener->id,
        ],
    ];

    $first = app(RecordPaymentSnapshot::class)->fromInvoice($invoice);
    $second = app(RecordPaymentSnapshot::class)->fromInvoice($invoice);

    expect($first)->not->toBeNull()
        ->and($second?->id)->toBe($first?->id)
        ->and($first?->gross_amount_cents)->toBe(1000)
        ->and($first?->platform_fee_cents)->toBe(100)
        ->and($first?->creator_payout_cents)->toBe(900)
        ->and($first?->status)->toBe(PaymentStatus::Succeeded);

    expect(PaymentSnapshot::query()->count())->toBe(1);
});

test('stripe webhook listener syncs connect account payout status', function () {
    $profile = CreatorProfile::factory()->payoutPending()->create();
    $accountId = $profile->stripe_connect_account_id;

    FakeStripeConnectService::enableAccount($accountId);

    app(SyncCreatorPayoutStatus::class)($profile->fresh());

    expect($profile->fresh()->payout_status)->toBe(CreatorPayoutStatus::Enabled);
});

test('stripe webhook processing records invoice payments', function () {
    $profile = CreatorProfile::factory()->create();
    $tier = Tier::factory()->for($profile)->active()->create();
    $listener = User::factory()->listener()->create();

    PlatformFeeSetting::factory()->create([
        'percentage_fee' => 10,
        'fixed_fee_cents' => 0,
        'currency' => 'usd',
        'effective_at' => now()->subDay(),
    ]);

    $webhookCall = WebhookCall::query()->create([
        'name' => 'stripe',
        'url' => route('stripe.webhooks'),
        'payload' => [
            'id' => 'evt_invoice_payment_succeeded',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_webhook_test',
                    'amount_paid' => 1500,
                    'currency' => 'usd',
                    'metadata' => [
                        'creator_profile_id' => (string) $profile->id,
                        'tier_id' => (string) $tier->id,
                        'listener_user_id' => (string) $listener->id,
                    ],
                ],
            ],
        ],
    ]);

    (new HandleInvoicePaymentSucceededJob($webhookCall))->handle(
        app(StripeWebhookHandler::class),
        app(LogWebhookActivity::class),
    );

    expect(PaymentSnapshot::query()->where('stripe_invoice_id', 'in_webhook_test')->exists())->toBeTrue();
});

test('public creator pages expose subscribable tiers with checkout links', function () {
    $profile = CreatorProfile::factory()->public()->payoutEnabled()->create();
    $tier = Tier::factory()->for($profile)->active()->create([
        'name' => 'Supporter',
    ]);

    $this->get(route('creators.show', $profile->handle))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tiers', 1)
            ->where('tiers.0.name', 'Supporter')
            ->where('tiers.0.is_subscribable', true)
            ->where('tiers.0.subscribe_url', route('creators.subscribe', [
                'profile' => $profile->handle,
                'tier' => $tier->id,
            ]))
        );
});
