<?php

use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

test('guests cannot access hls segment', function () {
    $upload = Upload::factory()->create([
        'hls_path' => 'hls/example/playlist.m3u8',
    ]);

    $this->getJson(route('uploads.hls.segment', [$upload, 'segment_000.ts']))
        ->assertUnauthorized();
});

test('owners are redirected to a signed segment url', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();
    $hlsPath = "hls/{$upload->uuid}/playlist.m3u8";

    $upload->update(['hls_path' => $hlsPath]);

    $segmentPath = "hls/{$upload->uuid}/segment_000.ts";

    Storage::disk('s3')->put($segmentPath, 'segment-bytes');

    $this->actingAs($user)
        ->get(route('uploads.hls.segment', [$upload, 'segment_000.ts']))
        ->assertRedirect();
});

test('invalid hls segment names are rejected', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();
    $hlsPath = "hls/{$upload->uuid}/playlist.m3u8";

    $upload->update(['hls_path' => $hlsPath]);

    $this->actingAs($user)
        ->get(route('uploads.hls.segment', [$upload, 'evil.ts']))
        ->assertNotFound();
});

test('hls segment returns not found when hls path is missing', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'hls_path' => null,
    ]);

    $this->actingAs($user)
        ->get(route('uploads.hls.segment', [$upload, 'segment_000.ts']))
        ->assertNotFound();
});
