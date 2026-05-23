<?php

use App\Actions\Upload\RedirectUploadHlsSegment;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Storage::fake('s3');
});

test('redirect upload hls segment redirects to a signed url', function () {
    $upload = Upload::factory()->create();
    $hlsPath = "hls/{$upload->uuid}/playlist.m3u8";
    $segmentPath = "hls/{$upload->uuid}/segment_000.ts";

    $upload->update(['hls_path' => $hlsPath]);

    Storage::disk('s3')->put($segmentPath, 'segment-bytes');

    $response = app(RedirectUploadHlsSegment::class)->execute($upload, 'segment_000.ts');

    expect($response->isRedirect())->toBeTrue();
});

test('redirect upload hls segment aborts for invalid segment names', function () {
    $upload = Upload::factory()->create([
        'hls_path' => 'hls/example/playlist.m3u8',
    ]);

    app(RedirectUploadHlsSegment::class)->execute($upload, 'evil.ts');
})->throws(NotFoundHttpException::class);

test('redirect upload hls segment aborts when hls path is missing', function () {
    $upload = Upload::factory()->create([
        'hls_path' => null,
    ]);

    app(RedirectUploadHlsSegment::class)->execute($upload, 'segment_000.ts');
})->throws(NotFoundHttpException::class);
