<?php

use App\Models\Upload;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated users see upload stats on the dashboard', function () {
    $user = User::factory()->create();

    Upload::factory()->for($user)->ready()->create([
        'size' => 1_024,
    ]);
    Upload::factory()->for($user)->ready()->create([
        'size' => 2_048,
    ]);
    Upload::factory()->for($user)->processing()->create([
        'size' => 512,
    ]);
    Upload::factory()->for($user)->failed()->create([
        'size' => 256,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.total_ready', 2)
            ->where('stats.total_processing', 1)
            ->where('stats.total_failed', 1)
            ->where('stats.total_storage_bytes', 3_840)
        );
});

test('dashboard stats only include the authenticated users uploads', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Upload::factory()->for($user)->ready()->create([
        'size' => 1_024,
    ]);
    Upload::factory()->for($otherUser)->ready()->create([
        'size' => 9_999,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.total_ready', 1)
            ->where('stats.total_storage_bytes', 1_024)
        );
});
