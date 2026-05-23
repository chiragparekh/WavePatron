<?php

use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

test('guests cannot access waveform json', function () {
    $upload = Upload::factory()->ready()->create();

    $this->getJson(route('uploads.waveform', $upload))
        ->assertUnauthorized();
});

test('users cannot access another users waveform json', function () {
    $upload = Upload::factory()->ready()->create();
    $waveform = [
        'version' => 1,
        'length' => 2,
        'data' => [[-0.5, 0.5], [-0.25, 0.75]],
    ];

    Storage::disk('s3')->put(
        $upload->waveform_path,
        json_encode($waveform, JSON_THROW_ON_ERROR),
    );

    $this->actingAs(User::factory()->create())
        ->get(route('uploads.waveform', $upload))
        ->assertForbidden();
});

test('owners receive waveform json with correct content type', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->ready()->create();
    $waveform = [
        'version' => 1,
        'length' => 2,
        'data' => [[-0.5, 0.5], [-0.25, 0.75]],
    ];

    Storage::disk('s3')->put(
        $upload->waveform_path,
        json_encode($waveform, JSON_THROW_ON_ERROR),
    );

    $this->actingAs($user)
        ->get(route('uploads.waveform', $upload))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJson($waveform);
});

test('waveform json returns not found when waveform path is missing', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->ready()->create([
        'waveform_path' => null,
    ]);

    $this->actingAs($user)
        ->get(route('uploads.waveform', $upload))
        ->assertNotFound();
});
