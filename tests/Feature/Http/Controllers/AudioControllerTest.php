<?php

use App\Enums\AppMode;
use App\Enums\SubscriptionStatus;
use App\Models\CreatorProfile;
use App\Models\Subscription;
use App\Models\Tier;
use App\Models\Upload;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the audios page', function () {
    $this->get(route('audios.index'))
        ->assertRedirect(route('login'));
});

test('creator mode users see only their own ready uploads', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();
    $otherUser = User::factory()->creator()->create();

    $readyUpload = Upload::factory()
        ->for($user)
        ->ready()
        ->withMetadata()
        ->create(['original_name' => 'mine.mp3']);

    Upload::factory()
        ->for($otherUser)
        ->ready()
        ->published()
        ->withMetadata()
        ->create(['original_name' => 'theirs.mp3']);

    $this->actingAs($user)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data', 1)
            ->where('uploads.data.0.uuid', $readyUpload->uuid)
            ->where('uploads.data.0.original_name', 'mine.mp3')
        );
});

test('creator mode excludes non-ready uploads from the audios page', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    Upload::factory()->for($user)->ready()->withMetadata()->create();
    Upload::factory()->for($user)->processing()->withMetadata()->create();
    Upload::factory()->for($user)->uploaded()->withMetadata()->create();
    Upload::factory()->for($user)->pendingUpload()->withMetadata()->create();

    $this->actingAs($user)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data', 1)
        );
});

test('listener mode shows free published ready audio from any creator', function () {
    $listener = User::factory()->listener()->create();
    $creator = User::factory()->creator()->create();
    CreatorProfile::factory()->for($creator)->create();

    $accessibleUpload = Upload::factory()
        ->for($creator)
        ->ready()
        ->published()
        ->free()
        ->withMetadata()
        ->create(['original_name' => 'free.mp3']);

    Upload::factory()
        ->for($creator)
        ->ready()
        ->draft()
        ->free()
        ->create(['original_name' => 'draft.mp3']);

    Upload::factory()
        ->for($creator)
        ->processing()
        ->published()
        ->free()
        ->create(['original_name' => 'processing.mp3']);

    $this->actingAs($listener)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data', 1)
            ->where('uploads.data.0.uuid', $accessibleUpload->uuid)
            ->where('uploads.data.0.creator.display_name', $creator->creatorProfile->display_name)
            ->missing('processingUploads')
        );
});

test('listener mode shows premium published ready audio with an accessible subscription', function () {
    $listener = User::factory()->listener()->create();
    $creator = User::factory()->creator()->create();
    $profile = CreatorProfile::factory()->for($creator)->create();
    $tier = Tier::factory()->for($profile)->active()->create();

    $premiumUpload = Upload::factory()
        ->for($creator)
        ->ready()
        ->published()
        ->premium()
        ->withMetadata()
        ->create(['original_name' => 'premium.mp3']);

    Subscription::query()->create([
        'user_id' => $listener->id,
        'type' => 'creator-'.$profile->id,
        'stripe_id' => 'sub_audios_library',
        'stripe_status' => 'active',
        'stripe_price' => $tier->stripe_monthly_price_id,
        'creator_profile_id' => $profile->id,
        'tier_id' => $tier->id,
        'local_status' => SubscriptionStatus::Active,
    ]);

    $this->actingAs($listener)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data', 1)
            ->where('uploads.data.0.uuid', $premiumUpload->uuid)
        );
});

test('listener mode hides premium published ready audio without an accessible subscription', function () {
    $listener = User::factory()->listener()->create();
    $creator = User::factory()->creator()->create();
    CreatorProfile::factory()->for($creator)->create();

    Upload::factory()
        ->for($creator)
        ->ready()
        ->published()
        ->premium()
        ->create(['original_name' => 'locked.mp3']);

    $this->actingAs($listener)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data', 0)
        );
});

test('audios page includes metadata and generated playback and waveform urls', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    $upload = Upload::factory()
        ->for($user)
        ->ready()
        ->withMetadata()
        ->create();

    $this->actingAs($user)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data.0', fn (Assert $item) => $item
                ->where('uuid', $upload->uuid)
                ->where('original_name', $upload->original_name)
                ->has('uploaded_at')
                ->has('metadata', fn (Assert $metadata) => $metadata
                    ->where('title', 'Episode 10')
                    ->where('artist', 'John Doe')
                    ->where('duration', '01:25:10')
                    ->where('duration_seconds', 5110.24)
                    ->where('codec', 'mp3')
                    ->where('bitrate', 128_000)
                    ->where('sample_rate', 44_100)
                )
                ->where(
                    'hls_playlist_url',
                    route('uploads.hls.playlist', $upload),
                )
                ->where(
                    'waveform_url',
                    route('uploads.waveform', $upload),
                )
            )
        );
});

test('creator mode audios page does not include processing uploads on initial load', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    Upload::factory()->for($user)->ready()->withMetadata()->create();
    Upload::factory()->for($user)->processing()->create([
        'original_name' => 'processing.mp3',
    ]);

    $this->actingAs($user)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->missing('processingUploads')
        );
});

test('creator mode audios page includes processing uploads when requested as a partial reload', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    Upload::factory()->for($user)->ready()->withMetadata()->create();
    $processingUpload = Upload::factory()->for($user)->processing()->create([
        'original_name' => 'processing.mp3',
    ]);
    Upload::factory()->for($user)->failed()->create([
        'original_name' => 'failed.mp3',
    ]);
    Upload::factory()->for($user)->pendingUpload()->create([
        'original_name' => 'pending.mp3',
    ]);

    $this->actingAs($user)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->reloadOnly('processingUploads', fn (Assert $reload) => $reload
                ->has('processingUploads', 2)
                ->where('processingUploads.1.uuid', $processingUpload->uuid)
                ->where('processingUploads.1.original_name', 'processing.mp3')
                ->where('processingUploads.1.status', 'processing')
                ->has('processingUploads.1.step_statuses')
            )
        );
});

test('audios page paginates results with twenty per page by default', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    Upload::factory()
        ->for($user)
        ->ready()
        ->count(21)
        ->create();

    $this->actingAs($user)
        ->get(route('audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data', 20)
            ->where('uploads.meta.per_page', 20)
            ->where('uploads.meta.total', 21)
            ->where('uploads.meta.last_page', 2)
        );
});

test('audios page returns the next page of uploads', function () {
    $user = User::factory()
        ->creatorAndListener()
        ->withActiveMode(AppMode::Creator)
        ->create();

    Upload::factory()
        ->for($user)
        ->ready()
        ->count(21)
        ->create();

    $this->actingAs($user)
        ->get(route('audios.index', ['page' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audios/index')
            ->has('uploads.data', 1)
            ->where('uploads.meta.current_page', 2)
            ->where('uploads.meta.last_page', 2)
        );
});
