<?php

use App\Actions\Activity\LogAppActivity;
use App\Actions\Payment\RecordPaymentSnapshot;
use App\Actions\Webhook\LogWebhookActivity;
use App\Enums\AppMode;
use App\Enums\SubscriptionStatus;
use App\Models\CreatorFeeOverride;
use App\Models\CreatorProfile;
use App\Models\PlatformFeeSetting;
use App\Models\Subscription;
use App\Models\Tier;
use App\Models\User;
use App\Models\WebhookCall;
use Laravel\Cashier\Events\WebhookReceived;
use Spatie\Activitylog\Models\Activity;

test('login redirects are activity logged with destination metadata', function () {
    $user = User::factory()->listener()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $activity = Activity::query()
        ->where('event', 'auth_redirect')
        ->where('causer_id', $user->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('auth')
        ->and($activity->properties['redirect_to'])->toBe(route('dashboard', absolute: false));
});

test('account mode switches are activity logged with before and after values', function () {
    $user = User::factory()->creatorAndListener()->create();

    $this->actingAs($user)
        ->put(route('account.mode.update'), ['mode' => AppMode::Creator->value])
        ->assertRedirect(route('creator.onboarding', absolute: false));

    $activity = Activity::query()
        ->where('event', 'mode_switched')
        ->where('causer_id', $user->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('auth')
        ->and($activity->properties['from'])->toBe(AppMode::Listener->value)
        ->and($activity->properties['to'])->toBe(AppMode::Creator->value);
});

test('payment snapshots are activity logged when recorded from invoices', function () {
    $profile = CreatorProfile::factory()->payoutEnabled()->create();
    $listener = User::factory()->listener()->create();
    $tier = Tier::factory()->for($profile)->active()->create();

    $subscription = Subscription::query()->create([
        'user_id' => $listener->id,
        'type' => 'default',
        'stripe_id' => 'sub_test_payment_log',
        'stripe_status' => 'active',
        'creator_profile_id' => $profile->id,
        'tier_id' => $tier->id,
        'local_status' => SubscriptionStatus::Active,
    ]);

    PlatformFeeSetting::factory()->create([
        'percentage_fee' => 10,
        'fixed_fee_cents' => 0,
        'currency' => 'usd',
        'effective_at' => now()->subDay(),
    ]);

    $snapshot = app(RecordPaymentSnapshot::class)->fromInvoice([
        'id' => 'in_activity_log_test',
        'amount_paid' => 1000,
        'currency' => 'usd',
        'subscription' => $subscription->stripe_id,
        'metadata' => [
            'creator_profile_id' => (string) $profile->id,
            'tier_id' => (string) $tier->id,
            'listener_user_id' => (string) $listener->id,
        ],
    ]);

    expect($snapshot)->not->toBeNull();

    $activity = Activity::query()
        ->where('event', 'payment_recorded')
        ->where('subject_id', $snapshot->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('payment')
        ->and($activity->causer_id)->toBe($listener->id)
        ->and($activity->properties['gross_amount_cents'])->toBe(1000);
});

test('subscription webhook syncs are activity logged when local status changes', function () {
    $listener = User::factory()->listener()->create();
    $profile = CreatorProfile::factory()->create();
    $tier = Tier::factory()->for($profile)->active()->create();

    $subscription = Subscription::query()->create([
        'user_id' => $listener->id,
        'type' => 'default',
        'stripe_id' => 'sub_activity_sync_test',
        'stripe_status' => 'active',
        'creator_profile_id' => $profile->id,
        'tier_id' => $tier->id,
        'local_status' => SubscriptionStatus::Active,
    ]);

    event(new WebhookReceived([
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => $subscription->stripe_id,
                'status' => 'canceled',
                'metadata' => [
                    'creator_profile_id' => (string) $profile->id,
                    'tier_id' => (string) $tier->id,
                ],
            ],
        ],
    ]));

    $activity = Activity::query()
        ->where('event', 'subscription_synced')
        ->where('subject_id', $subscription->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('subscription')
        ->and($activity->properties['local_status']['from'])->toBe(SubscriptionStatus::Active->value)
        ->and($activity->properties['local_status']['to'])->toBe(SubscriptionStatus::Cancelled->value);
});

test('platform fee settings log create and update actions from filament', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $setting = PlatformFeeSetting::factory()->create([
        'percentage_fee' => 12.5,
        'fixed_fee_cents' => 30,
        'currency' => 'usd',
        'effective_at' => now()->addDay(),
    ]);

    app(LogAppActivity::class)->execute(
        event: 'fee_setting_created',
        subject: $setting,
        causer: $admin,
        properties: ['attributes' => $setting->only(['percentage_fee', 'fixed_fee_cents', 'currency', 'effective_at'])],
        logName: 'admin',
    );

    $setting->update(['percentage_fee' => 15]);

    app(LogAppActivity::class)->execute(
        event: 'fee_setting_updated',
        subject: $setting,
        causer: $admin,
        properties: [
            'changes' => [
                'percentage_fee' => ['from' => '12.50', 'to' => '15.00'],
            ],
        ],
        logName: 'admin',
    );

    expect(Activity::query()->where('event', 'fee_setting_created')->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'fee_setting_updated')->exists())->toBeTrue();
});

test('creator fee overrides log create actions', function () {
    $admin = User::factory()->admin()->create();
    $profile = CreatorProfile::factory()->create();
    $override = CreatorFeeOverride::factory()->for($profile)->create();

    app(LogAppActivity::class)->execute(
        event: 'creator_fee_override_created',
        subject: $override,
        causer: $admin,
        properties: ['attributes' => $override->only(['creator_profile_id', 'percentage_fee', 'fixed_fee_cents', 'currency', 'effective_at'])],
        logName: 'admin',
    );

    expect(Activity::query()
        ->where('event', 'creator_fee_override_created')
        ->where('causer_id', $admin->id)
        ->exists())->toBeTrue();
});

test('webhook processing outcomes remain activity logged', function () {
    $webhookCall = WebhookCall::query()->create([
        'name' => 'stripe',
        'url' => route('stripe.webhooks'),
        'payload' => [
            'id' => 'evt_activity_log',
            'type' => 'account.updated',
            'data' => ['object' => ['id' => 'acct_123']],
        ],
    ]);

    app(LogWebhookActivity::class)->succeeded($webhookCall);

    expect(Activity::query()
        ->where('event', 'webhook.processed')
        ->where('subject_id', $webhookCall->id)
        ->exists())->toBeTrue();
});

test('admins can browse and view activity logs in filament', function () {
    $admin = User::factory()->admin()->create();

    $activity = Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'mode_switched',
        'event' => 'mode_switched',
        'causer_type' => User::class,
        'causer_id' => $admin->id,
        'subject_type' => User::class,
        'subject_id' => $admin->id,
        'properties' => ['from' => 'listener', 'to' => 'creator'],
    ]);

    $this->actingAs($admin)
        ->get('/admin/activities')
        ->assertSuccessful()
        ->assertSee('mode_switched');

    $this->actingAs($admin)
        ->get("/admin/activities/{$activity->id}")
        ->assertSuccessful()
        ->assertSee('listener')
        ->assertSee('creator');
});

test('non admin users cannot access activity logs in filament', function () {
    $listener = User::factory()->listener()->create();

    $this->actingAs($listener)
        ->get('/admin/activities')
        ->assertForbidden();
});
