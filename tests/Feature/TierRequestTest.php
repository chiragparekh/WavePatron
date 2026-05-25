<?php

use App\Actions\Tier\ApproveTier;
use App\Actions\Tier\RejectTier;
use App\Contracts\CreatesStripeTierProduct;
use App\Enums\CreatorPayoutStatus;
use App\Enums\TierStatus;
use App\Models\CreatorProfile;
use App\Models\Tier;
use App\Models\User;
use App\Support\Stripe\FakeStripeTierProductCreator;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

test('creators can list their tiers', function () {
    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->create();

    $tier = Tier::factory()->for($profile)->create([
        'name' => 'Supporter',
    ]);

    Tier::factory()->create();

    $this->actingAs($creator)
        ->get(route('creator.tiers.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('creator/tiers/index')
            ->has('tiers', 1)
            ->where('tiers.0.id', $tier->id)
            ->where('tiers.0.name', 'Supporter')
        );
});

test('creators without a profile cannot access tier routes', function () {
    $creator = User::factory()->creator()->create();

    $this->actingAs($creator)
        ->get(route('creator.tiers.index'))
        ->assertNotFound();
});

test('creators can create and update tier drafts', function () {
    $creator = User::factory()->creator()->create();
    CreatorProfile::factory()->for($creator)->create();

    $this->actingAs($creator)
        ->post(route('creator.tiers.store'), [
            'name' => 'Supporter',
            'benefits' => ['Early access', 'Bonus tracks'],
            'monthly_price' => '5.00',
            'annual_price' => '50.00',
        ])
        ->assertRedirect();

    $tier = Tier::query()->first();

    expect($tier)->not->toBeNull()
        ->and($tier->status)->toBe(TierStatus::Draft)
        ->and($tier->monthly_price_cents)->toBe(500)
        ->and($tier->annual_price_cents)->toBe(5000);

    $this->actingAs($creator)
        ->put(route('creator.tiers.update', $tier), [
            'name' => 'Super Supporter',
            'benefits' => ['Early access'],
            'monthly_price' => '7.50',
        ])
        ->assertRedirect(route('creator.tiers.edit', $tier));

    $tier->refresh();

    expect($tier->name)->toBe('Super Supporter')
        ->and($tier->monthly_price_cents)->toBe(750)
        ->and($tier->annual_price_cents)->toBeNull();

    $events = Activity::query()
        ->forSubject($tier)
        ->pluck('event')
        ->all();

    expect($events)->toContain('price_changed');
});

test('tier requests require valid pricing and benefits', function () {
    $creator = User::factory()->creator()->create();
    CreatorProfile::factory()->for($creator)->create();

    $this->actingAs($creator)
        ->post(route('creator.tiers.store'), [
            'name' => '',
            'benefits' => [],
            'monthly_price' => '-1',
        ])
        ->assertSessionHasErrors(['name', 'benefits', 'monthly_price']);
});

test('creators can submit tier requests for admin review', function () {
    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->create();
    $tier = Tier::factory()->for($profile)->draft()->create();

    $this->actingAs($creator)
        ->post(route('creator.tiers.submit', $tier))
        ->assertRedirect(route('creator.tiers.index'));

    expect($tier->fresh()->status)->toBe(TierStatus::Requested);

    $events = Activity::query()
        ->forSubject($tier)
        ->pluck('event')
        ->all();

    expect($events)->toContain('requested');
});

test('creators cannot submit tiers that are not editable', function () {
    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->create();
    $tier = Tier::factory()->for($profile)->requested()->create();

    $this->actingAs($creator)
        ->post(route('creator.tiers.submit', $tier))
        ->assertForbidden();
});

test('admin approval stores stripe references and transitions tier to approved', function () {
    $admin = User::factory()->admin()->create();
    $profile = CreatorProfile::factory()->create();
    $tier = Tier::factory()->for($profile)->requested()->create([
        'annual_price_cents' => 5000,
    ]);

    $this->app->bind(CreatesStripeTierProduct::class, FakeStripeTierProductCreator::class);

    app(ApproveTier::class)->execute($tier, $admin);

    $tier->refresh();

    expect($tier->status)->toBe(TierStatus::Approved)
        ->and($tier->stripe_product_id)->toStartWith('prod_')
        ->and($tier->stripe_monthly_price_id)->toStartWith('price_monthly_')
        ->and($tier->stripe_annual_price_id)->toStartWith('price_annual_');

    $events = Activity::query()
        ->forSubject($tier)
        ->pluck('event')
        ->all();

    expect($events)->toContain('approved');
});

test('admin rejection transitions tier to rejected and is logged', function () {
    $admin = User::factory()->admin()->create();
    $tier = Tier::factory()->requested()->create();

    app(RejectTier::class)->execute($tier, $admin);

    expect($tier->fresh()->status)->toBe(TierStatus::Rejected);

    $events = Activity::query()
        ->forSubject($tier)
        ->pluck('event')
        ->all();

    expect($events)->toContain('rejected');
});

test('creators can activate approved tiers and archive active tiers', function () {
    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->create();
    $tier = Tier::factory()->for($profile)->approved()->create();

    $this->actingAs($creator)
        ->post(route('creator.tiers.activate', $tier))
        ->assertRedirect(route('creator.tiers.index'));

    expect($tier->fresh()->status)->toBe(TierStatus::Active);

    $this->actingAs($creator)
        ->post(route('creator.tiers.archive', $tier))
        ->assertRedirect(route('creator.tiers.index'));

    expect($tier->fresh()->status)->toBe(TierStatus::Archived);

    $events = Activity::query()
        ->forSubject($tier)
        ->pluck('event')
        ->all();

    expect($events)->toContain('activated', 'archived');
});

test('paid tiers are not subscribable until creator payout status is enabled', function () {
    $profile = CreatorProfile::factory()->create([
        'payout_status' => CreatorPayoutStatus::Pending,
    ]);

    $tier = Tier::factory()->for($profile)->active()->create([
        'monthly_price_cents' => 500,
    ]);

    expect($tier->isSubscribable())->toBeFalse();

    $profile->update(['payout_status' => CreatorPayoutStatus::Enabled]);

    expect($tier->fresh()->isSubscribable())->toBeTrue();
});

test('public creator profiles expose approved and active tiers', function () {
    $profile = CreatorProfile::factory()->public()->create();
    $visible = Tier::factory()->for($profile)->approved()->create(['name' => 'Visible tier']);
    Tier::factory()->for($profile)->draft()->create();
    Tier::factory()->for($profile)->archived()->create();

    $this->get(route('creators.show', $profile))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('creators/show')
            ->has('tiers', 1)
            ->where('tiers.0.id', (string) $visible->id)
            ->where('tiers.0.name', 'Visible tier')
            ->where('tiers.0.is_subscribable', false)
        );
});

test('listeners cannot access creator tier management routes', function () {
    $listener = User::factory()->listener()->create();

    $this->actingAs($listener)
        ->get(route('creator.tiers.index'))
        ->assertForbidden();
});
