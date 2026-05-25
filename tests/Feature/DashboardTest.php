<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated listeners see the listener dashboard at the canonical url', function () {
    $user = User::factory()->listener()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('listener/dashboard'));
});

test('legacy listener dashboard redirects to the canonical dashboard', function () {
    $user = User::factory()->listener()->create();

    $this->actingAs($user)
        ->get(route('listener.dashboard'))
        ->assertRedirect(route('dashboard'));
});

test('legacy creator dashboard redirects to the canonical dashboard', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->get(route('creator.dashboard'))
        ->assertRedirect(route('dashboard'));
});
