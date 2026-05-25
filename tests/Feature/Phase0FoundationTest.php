<?php

use App\Enums\AppMode;
use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Enums\CreatorPayoutStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role as AppRole;
use App\Enums\SubscriptionStatus;
use App\Enums\TierStatus;
use App\Models\CreatorProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

test('phase 0 package configuration files are published', function () {
    expect(config('permission'))->toBeArray()
        ->and(config('activitylog'))->toBeArray()
        ->and(config('cashier'))->toBeArray()
        ->and(config('stripe-webhooks'))->toBeArray()
        ->and(config('webhook-client'))->toBeArray();
});

test('shared foundation enums are defined', function () {
    expect(AppMode::Listener->value)->toBe('listener')
        ->and(AudioAccessLevel::Premium->value)->toBe('premium')
        ->and(AudioPublishStatus::Published->value)->toBe('published')
        ->and(CreatorPayoutStatus::Enabled->value)->toBe('enabled')
        ->and(TierStatus::Requested->value)->toBe('requested')
        ->and(SubscriptionStatus::Active->value)->toBe('active')
        ->and(PaymentStatus::Succeeded->value)->toBe('succeeded');
});

test('role seeder creates admin creator and listener roles', function () {
    Role::query()->delete();

    (new RoleSeeder)->run();

    expect(Role::pluck('name')->all())->toEqual(AppRole::values());
});

test('registered users receive listener and creator roles by default', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Dual Role User',
        'email' => 'dual-role@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'dual-role@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole(AppRole::Listener->value))->toBeTrue()
        ->and($user->hasRole(AppRole::Creator->value))->toBeTrue();
});

test('dashboard redirects admins to the filament panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect('/admin');
});

test('dashboard redirects creators without a profile to onboarding', function () {
    $creator = User::factory()->creator()->create();

    $this->actingAs($creator)
        ->get(route('dashboard'))
        ->assertRedirect(route('creator.onboarding'));
});

test('dashboard redirects creators with a profile to the canonical dashboard page', function () {
    $creator = User::factory()->creator()->create();
    CreatorProfile::factory()->for($creator)->create();

    $this->actingAs($creator)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('creator/dashboard'));
});

test('dashboard renders the listener dashboard for listeners', function () {
    $listener = User::factory()->listener()->create();

    $this->actingAs($listener)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('listener/dashboard'));
});

test('admins can access the filament admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

test('non admin users cannot access the filament admin panel', function () {
    $listener = User::factory()->listener()->create();

    $this->actingAs($listener)
        ->get('/admin')
        ->assertForbidden();
});

test('listener dashboard renders at the canonical url', function () {
    $user = User::factory()->listener()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('listener/dashboard')
        );
});
