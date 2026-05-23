<?php

use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

test('guests cannot access hls playlist', function () {
    $upload = Upload::factory()->create([
        'hls_path' => 'hls/example/playlist.m3u8',
    ]);

    $this->getJson(route('uploads.hls.playlist', $upload))
        ->assertUnauthorized();
});

test('users cannot access another users hls playlist', function () {
    $upload = Upload::factory()->create([
        'hls_path' => 'hls/example/playlist.m3u8',
    ]);

    Storage::disk('s3')->put($upload->hls_path, '#EXTM3U');

    $this->actingAs(User::factory()->create())
        ->get(route('uploads.hls.playlist', $upload))
        ->assertForbidden();
});

test('owners receive hls playlist with correct content type', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();
    $hlsPath = "hls/{$upload->uuid}/playlist.m3u8";

    $upload->update(['hls_path' => $hlsPath]);

    Storage::disk('s3')->put($hlsPath, "#EXTM3U\nsegment_000.ts\n");

    $this->actingAs($user)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl')
        ->assertSee('segment_000.ts', false);
});

test('hls playlist returns not found when hls path is missing', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'hls_path' => null,
    ]);

    $this->actingAs($user)
        ->get(route('uploads.hls.playlist', $upload))
        ->assertNotFound();
});
