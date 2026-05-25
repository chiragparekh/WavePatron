<?php

use App\Enums\AppMode;
use App\Enums\Role;
use App\Models\CreatorProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('listeners are redirected to the dashboard after login', function () {
    $user = User::factory()->listener()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('creators are redirected to creator onboarding when they have no profile', function () {
    $user = User::factory()->creator()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('creator.onboarding', absolute: false));
});

test('creators with a profile are redirected to the dashboard after login', function () {
    $user = User::factory()->creator()->create();
    CreatorProfile::factory()->for($user)->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});

test('admins are redirected to the filament panel after login', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/admin');
});

test('plain factory users receive both listener and creator roles by default', function () {
    $user = User::factory()->create();

    expect($user->hasRole(Role::Listener->value))->toBeTrue()
        ->and($user->hasRole(Role::Creator->value))->toBeTrue();
});

test('dual role users default to the dashboard after login', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});

test('dual role users can switch to creator mode', function () {
    $user = User::factory()->creatorAndListener()->create();

    $this->actingAs($user)
        ->put(route('account.mode.update'), ['mode' => AppMode::Creator->value])
        ->assertRedirect(route('creator.onboarding', absolute: false));

    expect($user->fresh()->active_mode)->toBe(AppMode::Creator);
});

test('dual role users can switch back to listener mode', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    $this->actingAs($user)
        ->put(route('account.mode.update'), ['mode' => AppMode::Listener->value])
        ->assertRedirect(route('dashboard', absolute: false));

    expect($user->fresh()->active_mode)->toBe(AppMode::Listener);
});

test('listener only users cannot switch to creator mode', function () {
    $user = User::factory()->listener()->create();

    $this->actingAs($user)
        ->put(route('account.mode.update'), ['mode' => AppMode::Creator->value])
        ->assertSessionHasErrors('mode');

    expect($user->fresh()->active_mode)->toBe(AppMode::Listener);
});

test('invalid account modes are rejected', function () {
    $user = User::factory()->creatorAndListener()->create();

    $this->actingAs($user)
        ->put(route('account.mode.update'), ['mode' => 'admin'])
        ->assertSessionHasErrors('mode');
});

test('dashboard redirects dual role users in creator mode without a profile to onboarding', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('creator.onboarding'));
});

test('dashboard renders the creator dashboard for dual role users in creator mode with a profile', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    CreatorProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('creator/dashboard'));
});

test('legacy listener dashboard redirects to the canonical dashboard', function () {
    $user = User::factory()->creatorAndListener()->create();

    $this->actingAs($user)
        ->get(route('listener.dashboard'))
        ->assertRedirect(route('dashboard'));
});

test('legacy creator dashboard redirects to the canonical dashboard', function () {
    $user = User::factory()->creator()->create();
    CreatorProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('creator.dashboard'))
        ->assertRedirect(route('dashboard'));
});

test('authenticated app pages share account mode state', function () {
    $user = User::factory()->creatorAndListener()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('appMode.active', AppMode::Listener->value)
            ->where('appMode.available', [AppMode::Listener->value, AppMode::Creator->value])
            ->where('appMode.canSwitch', true)
        );
});

test('listener only users do not see account mode switching', function () {
    $user = User::factory()->listener()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('appMode.active', AppMode::Listener->value)
            ->where('appMode.available', [AppMode::Listener->value])
            ->where('appMode.canSwitch', false)
        );
});

test('creator onboarding page is reachable for authenticated creators', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->get(route('creator.onboarding'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('creator/onboarding'));
});
