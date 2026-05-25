<?php

use App\Models\CreatorProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated listeners see the listening focused dashboard', function () {
    $user = User::factory()->listener()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('listener/dashboard')
        );
});

test('authenticated creators with a profile see the creator dashboard', function () {
    $user = User::factory()->creator()->create();
    CreatorProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('creator/dashboard')
        );
});
