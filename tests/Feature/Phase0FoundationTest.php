<?php

use App\Enums\AppMode;
use App\Enums\AudioAccessLevel;
use App\Enums\CreatorPayoutStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role as AppRole;
use App\Enums\SubscriptionStatus;
use App\Enums\TierStatus;
use App\Models\Upload;
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

test('registered users receive the listener role by default', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Listener User',
        'email' => 'listener@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'listener@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole(AppRole::Listener->value))->toBeTrue();
});

test('dashboard redirects admins to the filament panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect('/admin');
});

test('dashboard redirects creators to the creator dashboard', function () {
    $creator = User::factory()->creator()->create();

    $this->actingAs($creator)
        ->get(route('dashboard'))
        ->assertRedirect(route('creator.dashboard'));
});

test('dashboard redirects listeners to the listener dashboard', function () {
    $listener = User::factory()->listener()->create();

    $this->actingAs($listener)
        ->get(route('dashboard'))
        ->assertRedirect(route('listener.dashboard'));
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

test('listener dashboard renders upload stats', function () {
    $user = User::factory()->listener()->create();

    Upload::factory()->for($user)->ready()->create([
        'size' => 1_024,
    ]);

    $this->actingAs($user)
        ->get(route('listener.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('listener/dashboard')
            ->where('stats.total_ready', 1)
            ->where('stats.total_storage_bytes', 1_024)
        );
});
