<?php

use App\Actions\Upload\ServeUploadHlsPlaylist;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Storage::fake('s3');
});

test('serve upload hls playlist returns manifest with correct content type', function () {
    $upload = Upload::factory()->create();
    $hlsPath = "hls/{$upload->uuid}/playlist.m3u8";

    $upload->update(['hls_path' => $hlsPath]);

    Storage::disk('s3')->put($hlsPath, "#EXTM3U\nsegment_000.ts\n");

    $response = app(ServeUploadHlsPlaylist::class)->execute($upload);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toBe('application/vnd.apple.mpegurl')
        ->and($response->getContent())->toContain('segment_000.ts');
});

test('serve upload hls playlist aborts when hls path is missing', function () {
    $upload = Upload::factory()->create([
        'hls_path' => null,
    ]);

    app(ServeUploadHlsPlaylist::class)->execute($upload);
})->throws(NotFoundHttpException::class);
