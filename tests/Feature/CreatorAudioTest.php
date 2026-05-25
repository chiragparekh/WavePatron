<?php

use App\Actions\Upload\ConfirmUpload;
use App\Contracts\ChecksSubscriptionAccess;
use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

test('creators can list their uploads on the creator audio page', function () {
    $creator = User::factory()->creator()->create();
    $otherCreator = User::factory()->creator()->create();

    $mine = Upload::factory()->for($creator)->ready()->create([
        'title' => 'My episode',
    ]);

    Upload::factory()->for($otherCreator)->ready()->create();

    $this->actingAs($creator)
        ->get(route('creator.audios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('creator/audios/index')
            ->has('uploads.data', 1)
            ->where('uploads.data.0.uuid', $mine->uuid)
            ->where('uploads.data.0.display_title', 'My episode')
        );
});

test('listeners cannot access creator audio management routes', function () {
    $listener = User::factory()->listener()->create();

    $this->actingAs($listener)
        ->get(route('creator.audios.index'))
        ->assertForbidden();
});

test('creators can update audio metadata publishing and access', function () {
    $creator = User::factory()->creator()->create();
    $upload = Upload::factory()->for($creator)->ready()->draft()->free()->create();

    $this->actingAs($creator)
        ->put(route('creator.audios.update', $upload), [
            'title' => 'Published episode',
            'description' => 'Show notes',
            'publish_status' => AudioPublishStatus::Published->value,
            'access_level' => AudioAccessLevel::Premium->value,
        ])
        ->assertRedirect(route('creator.audios.edit', $upload));

    $upload->refresh();

    expect($upload->title)->toBe('Published episode')
        ->and($upload->description)->toBe('Show notes')
        ->and($upload->publish_status)->toBe(AudioPublishStatus::Published)
        ->and($upload->access_level)->toBe(AudioAccessLevel::Premium);

    $events = Activity::query()
        ->forSubject($upload)
        ->pluck('event')
        ->all();

    expect($events)->toContain('published', 'access_level_changed', 'metadata_updated');
});

test('creators cannot publish audio before processing is ready', function () {
    $creator = User::factory()->creator()->create();
    $upload = Upload::factory()->for($creator)->processing()->draft()->create();

    $this->actingAs($creator)
        ->put(route('creator.audios.update', $upload), [
            'publish_status' => AudioPublishStatus::Published->value,
        ])
        ->assertSessionHasErrors('publish_status');

    expect($upload->fresh()->publish_status)->toBe(AudioPublishStatus::Draft);
});

test('creators cannot manage another creators upload', function () {
    $creator = User::factory()->creator()->create();
    $upload = Upload::factory()->for(User::factory()->creator())->ready()->create();

    $this->actingAs($creator)
        ->get(route('creator.audios.edit', $upload))
        ->assertForbidden();
});

test('listeners can access published free ready audio playback routes', function () {
    Storage::fake('s3');

    $creator = User::factory()->creator()->create();
    $listener = User::factory()->listener()->create();

    $upload = Upload::factory()
        ->for($creator)
        ->ready()
        ->published()
        ->free()
        ->create();

    Storage::disk('s3')->put($upload->hls_path, "#EXTM3U\nsegment_000.ts\n");
    Storage::disk('s3')->put(
        $upload->waveform_path,
        json_encode(['version' => 1, 'length' => 1, 'data' => [[-0.5, 0.5]]], JSON_THROW_ON_ERROR),
    );

    $this->actingAs($listener)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertSuccessful();

    $this->actingAs($listener)
        ->get(route('uploads.waveform', $upload))
        ->assertSuccessful();
});

test('listeners cannot access draft audio playback routes', function () {
    Storage::fake('s3');

    $creator = User::factory()->creator()->create();
    $listener = User::factory()->listener()->create();

    $upload = Upload::factory()
        ->for($creator)
        ->ready()
        ->draft()
        ->free()
        ->create([
            'hls_path' => 'hls/example/playlist.m3u8',
            'waveform_path' => 'waveforms/example.json',
        ]);

    Storage::disk('s3')->put($upload->hls_path, '#EXTM3U');
    Storage::disk('s3')->put($upload->waveform_path, '{}');

    $this->actingAs($listener)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertForbidden();

    $this->actingAs($listener)
        ->get(route('uploads.waveform', $upload))
        ->assertForbidden();
});

test('listeners cannot access premium audio without subscription access', function () {
    Storage::fake('s3');

    $creator = User::factory()->creator()->create();
    $listener = User::factory()->listener()->create();

    $upload = Upload::factory()
        ->for($creator)
        ->ready()
        ->published()
        ->premium()
        ->create();

    Storage::disk('s3')->put($upload->hls_path, '#EXTM3U');

    $this->actingAs($listener)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertForbidden();
});

test('listeners can access premium audio when subscription access is granted', function () {
    Storage::fake('s3');

    $creator = User::factory()->creator()->create();
    $listener = User::factory()->listener()->create();

    $upload = Upload::factory()
        ->for($creator)
        ->ready()
        ->published()
        ->premium()
        ->create();

    Storage::disk('s3')->put($upload->hls_path, '#EXTM3U');

    $this->app->bind(ChecksSubscriptionAccess::class, fn () => new class implements ChecksSubscriptionAccess
    {
        public function hasAccess(User $listener, Upload $upload): bool
        {
            return true;
        }
    });

    $this->actingAs($listener)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertSuccessful();
});

test('admins can access any ready audio playback routes', function () {
    Storage::fake('s3');

    $creator = User::factory()->creator()->create();
    $admin = User::factory()->admin()->create();

    $upload = Upload::factory()
        ->for($creator)
        ->ready()
        ->draft()
        ->premium()
        ->create();

    Storage::disk('s3')->put($upload->hls_path, '#EXTM3U');

    $this->actingAs($admin)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertSuccessful();
});

test('confirming an upload logs an uploaded activity event', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->pendingUpload()->create();

    Storage::fake('s3');
    Storage::disk('s3')->put($upload->path, 'audio');

    app(ConfirmUpload::class)->execute($upload);

    expect(Activity::query()->forSubject($upload)->where('event', 'uploaded')->exists())->toBeTrue();
});
