<?php

use App\Enums\UploadStatus;
use App\Jobs\ProcessUploadHls;
use App\Jobs\ProcessUploadMetadata;
use App\Jobs\ProcessUploadWaveform;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('s3');
});

test('guests cannot request a signed upload url', function () {
    $this->postJson(route('uploads.store'), validUploadPayload())
        ->assertUnauthorized();
});

test('authenticated users receive signed upload details', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('uploads.store'), validUploadPayload())
        ->assertSuccessful()
        ->assertJsonStructure(['uuid', 'url', 'headers', 'path', 'expires_at'])
        ->assertJsonMissing(['id']);
});

test('store creates an upload record with pending status', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('uploads.store'), validUploadPayload([
            'name' => 'demo.wav',
            'size' => 2_048,
            'type' => 'audio/wav',
        ]));

    $uuid = $response->json('uuid');

    expect(Str::isUuid($uuid, version: 7))->toBeTrue();

    $this->assertDatabaseHas('uploads', [
        'uuid' => $uuid,
        'user_id' => $user->id,
        'original_name' => 'demo.wav',
        'mime_type' => 'audio/wav',
        'size' => 2_048,
        'disk' => 's3',
        'status' => UploadStatus::PendingUpload->value,
    ]);

    $upload = Upload::query()->where('uuid', $uuid)->first();

    expect($upload)->not->toBeNull()
        ->and($response->json('path'))->toBe($upload->path)
        ->and($upload->path)->toContain($uuid)
        ->and($upload->step_statuses)->toBe(Upload::defaultStepStatuses());
});

test('object path is scoped under the authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('uploads.store'), validUploadPayload());

    $path = $response->json('path');

    expect($path)->toStartWith("uploads/{$user->id}/")
        ->and($path)->toMatch('/^uploads\/\d+\/[0-9a-f-]{36}\.mp3$/');
});

test('non-audio mime types are rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('uploads.store'), validUploadPayload([
            'type' => 'video/mp4',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

test('files over 500 mb are rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('uploads.store'), validUploadPayload([
            'size' => 524_288_001,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['size']);
});

test('owners can confirm an upload after the file exists on storage', function () {
    Bus::fake();

    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->create();

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    $this->actingAs($user)
        ->patchJson(route('uploads.update', $upload))
        ->assertSuccessful()
        ->assertJson([
            'uuid' => $upload->uuid,
            'status' => UploadStatus::Processing->value,
            'path' => $upload->path,
            'original_name' => $upload->original_name,
        ])
        ->assertJsonMissing(['id']);

    expect($upload->fresh())
        ->status->toBe(UploadStatus::Processing)
        ->uploaded_at->not->toBeNull();

    Bus::assertChained([
        new ProcessUploadMetadata($upload->uuid),
        new ProcessUploadWaveform($upload->uuid),
        new ProcessUploadHls($upload->uuid),
    ]);
});

test('guests cannot confirm an upload', function () {
    $upload = Upload::factory()->create();

    $this->patchJson(route('uploads.update', $upload))
        ->assertUnauthorized();
});

test('users cannot confirm another users upload', function () {
    $upload = Upload::factory()->create();

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    $this->actingAs(User::factory()->create())
        ->patchJson(route('uploads.update', $upload))
        ->assertForbidden();
});

test('confirm rejects uploads that are not pending', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    $this->actingAs($user)
        ->patchJson(route('uploads.update', $upload))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload']);
});

test('confirm rejects uploads missing from storage', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->create();

    $this->actingAs($user)
        ->patchJson(route('uploads.update', $upload))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload']);
});

test('owners can fetch upload status from the api', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->processing()->create();

    $this->actingAs($user)
        ->getJson(route('uploads.show', $upload))
        ->assertSuccessful()
        ->assertJson([
            'uuid' => $upload->uuid,
            'status' => UploadStatus::Processing->value,
            'path' => $upload->path,
            'original_name' => $upload->original_name,
        ])
        ->assertJsonMissing(['id']);
});

test('guests cannot fetch upload status from the api', function () {
    $upload = Upload::factory()->create();

    $this->getJson(route('uploads.show', $upload))
        ->assertUnauthorized();
});

test('users cannot fetch another users upload status from the api', function () {
    $upload = Upload::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('uploads.show', $upload))
        ->assertForbidden();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array{name: string, size: int, type: string}
 */
function validUploadPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'track.mp3',
        'size' => 1_024,
        'type' => 'audio/mpeg',
    ], $overrides);
}
