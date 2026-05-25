<?php

use App\Enums\AppMode;
use App\Models\User;

test('guests cannot access the upload page', function () {
    $this->get(route('uploads.create'))
        ->assertRedirect(route('login'));
});

test('listener only users cannot visit the upload page', function () {
    $user = User::factory()->listener()->create();

    $this->actingAs($user)
        ->get(route('uploads.create'))
        ->assertForbidden();
});

test('dual role users in listener mode cannot visit the upload page', function () {
    $user = User::factory()->creatorAndListener()->create();

    $this->actingAs($user)
        ->get(route('uploads.create'))
        ->assertForbidden();
});

test('creator mode users can visit the upload page', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->get(route('uploads.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('uploads/create'));
});

test('dual role users in creator mode can visit the upload page', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    $this->actingAs($user)
        ->get(route('uploads.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('uploads/create'));
});
