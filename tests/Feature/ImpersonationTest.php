<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use STS\FilamentImpersonate\Facades\Impersonation;

/**
 * @param  array<string, mixed>  $sessionData
 */
function startImpersonation(User $admin, User $target, array $sessionData = []): void
{
    foreach ($sessionData as $key => $value) {
        session()->put($key, $value);
    }

    expect(Impersonation::enter($admin, $target, 'web'))->toBeTrue();
}

afterEach(function () {
    if (Impersonation::isImpersonating()) {
        Impersonation::leave();
    }
});

test('admins can impersonate non admin users', function () {
    $admin = User::factory()->admin()->create();
    $listener = User::factory()->listener()->create();

    $this->actingAs($admin);

    startImpersonation($admin, $listener);

    expect(auth()->id())->toBe($listener->id)
        ->and(Impersonation::isImpersonating())->toBeTrue()
        ->and(Impersonation::getImpersonatorId())->toBe($admin->id);
});

test('admin users cannot be impersonated', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    expect($otherAdmin->canBeImpersonated())->toBeFalse();
    expect($admin->canImpersonate())->toBeTrue();
});

test('non admin users cannot impersonate others', function () {
    $listener = User::factory()->listener()->create();

    expect($listener->canImpersonate())->toBeFalse();
});

test('impersonated users cannot access the filament admin panel', function () {
    $admin = User::factory()->admin()->create();
    $listener = User::factory()->listener()->create();

    $this->actingAs($admin);
    startImpersonation($admin, $listener);

    $this->get('/admin')
        ->assertForbidden();
});

test('inertia pages share impersonation banner state while impersonating', function () {
    $admin = User::factory()->admin()->create();
    $listener = User::factory()->listener()->create([
        'name' => 'Listener Target',
        'email' => 'listener-target@example.com',
    ]);

    $this->actingAs($admin);
    startImpersonation($admin, $listener);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('impersonation.active', true)
            ->where('impersonation.user.name', 'Listener Target')
            ->where('impersonation.user.email', 'listener-target@example.com')
            ->where('impersonation.leaveUrl', route('filament-impersonate.leave'))
        );
});

test('inertia pages do not share impersonation state for normal sessions', function () {
    $listener = User::factory()->listener()->create();

    $this->actingAs($listener)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('impersonation', null));
});

test('leave impersonation restores the admin and returns to filament', function () {
    $admin = User::factory()->admin()->create();
    $listener = User::factory()->listener()->create();

    $this->actingAs($admin);
    startImpersonation($admin, $listener, [
        'impersonate.back_to' => '/admin/users',
    ]);

    $this->get(route('filament-impersonate.leave'))
        ->assertRedirect('/admin/users');

    expect(auth()->id())->toBe($admin->id)
        ->and(Impersonation::isImpersonating())->toBeFalse();

    $this->get('/admin')
        ->assertSuccessful();
});

test('impersonation start and leave are activity logged', function () {
    $admin = User::factory()->admin()->create();
    $listener = User::factory()->listener()->create();

    $this->actingAs($admin);
    startImpersonation($admin, $listener);

    expect(Activity::query()
        ->where('event', 'impersonation_started')
        ->where('causer_id', $admin->id)
        ->where('subject_id', $listener->id)
        ->exists())->toBeTrue();

    session()->put('impersonate.back_to', '/admin/users');

    $this->get(route('filament-impersonate.leave'));

    expect(Activity::query()
        ->where('event', 'impersonation_ended')
        ->where('causer_id', $admin->id)
        ->where('subject_id', $listener->id)
        ->exists())->toBeTrue();
});

test('admins can access the users resource in filament', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertSuccessful();
});
