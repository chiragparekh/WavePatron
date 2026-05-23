<?php

use App\Models\User;

test('guests cannot access the upload page', function () {
    $this->get(route('uploads.create'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the upload page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('uploads.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('uploads/create'));
});
