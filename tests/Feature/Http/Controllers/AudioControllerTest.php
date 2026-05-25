<?php

use App\Models\Upload;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the audios page', function () {
    $this->get(route('audios.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users see only their own ready uploads', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $readyUpload = Upload::factory()
        ->for($user)
        ->ready()
        ->withMetadata()
        ->create(['original_name' => 'mine.mp3']);

    Upload::factory()
        ->for($otherUser)
        ->ready()
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

test('non-ready uploads are excluded from the audios page', function () {
    $user = User::factory()->create();

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

test('audios page includes metadata and generated playback and waveform urls', function () {
    $user = User::factory()->create();

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

test('audios page does not include processing uploads on initial load', function () {
    $user = User::factory()->create();

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

test('audios page includes processing uploads when requested as a partial reload', function () {
    $user = User::factory()->create();

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
    $user = User::factory()->create();

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
    $user = User::factory()->create();

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
