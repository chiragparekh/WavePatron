<?php

use App\Enums\CreatorProfileVisibility;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

test('creators can create a profile during onboarding', function () {
    Storage::fake('public');

    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->post(route('creator.profile.store'), [
            'handle' => 'Studio-Alpha',
            'display_name' => 'Studio Alpha',
            'tagline' => 'Independent audio',
            'bio' => 'Weekly releases.',
            'categories' => 'music, podcast',
            'website' => 'https://studio-alpha.test',
            'support_email' => 'hello@studio-alpha.test',
            'visibility' => CreatorProfileVisibility::Public->value,
        ])
        ->assertRedirect(route('dashboard'));

    $profile = $user->fresh()->creatorProfile;

    expect($profile)->not->toBeNull()
        ->and($profile->handle)->toBe('studio-alpha')
        ->and($profile->display_name)->toBe('Studio Alpha')
        ->and($profile->visibility)->toBe(CreatorProfileVisibility::Public)
        ->and($profile->categories)->toBe(['music', 'podcast']);

    expect(Activity::query()
        ->where('subject_type', CreatorProfile::class)
        ->where('subject_id', $profile->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();
});

test('creators cannot create more than one profile', function () {
    $user = User::factory()->creator()->create();
    CreatorProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('creator.profile.store'), [
            'handle' => 'second-handle',
            'display_name' => 'Second',
            'visibility' => CreatorProfileVisibility::Hidden->value,
        ])
        ->assertForbidden();
});

test('creator handles must be unique', function () {
    $existing = CreatorProfile::factory()->create(['handle' => 'taken-handle']);
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->post(route('creator.profile.store'), [
            'handle' => $existing->handle,
            'display_name' => 'Another Creator',
            'visibility' => CreatorProfileVisibility::Hidden->value,
        ])
        ->assertSessionHasErrors('handle');
});

test('creators can update their profile and visibility changes are activity logged', function () {
    $user = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($user)->hidden()->create([
        'handle' => 'before-update',
    ]);

    $this->actingAs($user)
        ->put(route('creator.profile.update'), [
            'handle' => 'after-update',
            'display_name' => 'Updated Name',
            'tagline' => $profile->tagline,
            'bio' => $profile->bio,
            'categories' => '',
            'website' => $profile->website,
            'support_email' => $profile->support_email,
            'visibility' => CreatorProfileVisibility::Public->value,
        ])
        ->assertRedirect(route('creator.profile.edit'));

    $profile->refresh();

    expect($profile->handle)->toBe('after-update')
        ->and($profile->display_name)->toBe('Updated Name')
        ->and($profile->visibility)->toBe(CreatorProfileVisibility::Public);

    expect(Activity::query()
        ->where('subject_type', CreatorProfile::class)
        ->where('subject_id', $profile->id)
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});

test('public creator pages are visible when profile visibility is public', function () {
    $profile = CreatorProfile::factory()->public()->create([
        'handle' => 'public-creator',
        'display_name' => 'Public Creator',
    ]);

    $this->get(route('creators.show', $profile->handle))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('creators/show')
            ->where('profile.handle', 'public-creator')
            ->where('profile.display_name', 'Public Creator')
            ->where('freeAudio', [])
            ->where('premiumAudio', [])
            ->where('tiers', [])
        );
});

test('hidden creator profiles are not publicly accessible', function () {
    $profile = CreatorProfile::factory()->hidden()->create([
        'handle' => 'hidden-creator',
    ]);

    $this->get(route('creators.show', $profile->handle))->assertNotFound();
});

test('draft creator profiles are not publicly accessible', function () {
    $profile = CreatorProfile::factory()->draft()->create([
        'handle' => 'draft-creator',
    ]);

    $this->get(route('creators.show', $profile->handle))->assertNotFound();
});

test('creators with a profile can upload avatar images', function () {
    Storage::fake('public');

    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->post(route('creator.profile.store'), [
            'handle' => 'avatar-creator',
            'display_name' => 'Avatar Creator',
            'visibility' => CreatorProfileVisibility::Hidden->value,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect(route('dashboard'));

    $profile = $user->fresh()->creatorProfile;

    expect($profile->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($profile->avatar_path);
});

test('admins can access creator profiles in filament', function () {
    CreatorProfile::factory()->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/creator-profiles')
        ->assertSuccessful();
});

test('onboarding redirects creators who already have a profile', function () {
    $user = User::factory()->creator()->create();
    CreatorProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('creator.onboarding'))
        ->assertRedirect(route('creator.profile.edit'));
});
