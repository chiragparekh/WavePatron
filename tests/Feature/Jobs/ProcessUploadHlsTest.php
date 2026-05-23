<?php

use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Jobs\ProcessUploadHls;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

test('process upload hls generates segments and marks step completed', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () use ($upload) {
        seedFakeHlsOutput($upload);

        return Process::result();
    });

    (new ProcessUploadHls($upload->uuid))->handle();

    $upload->refresh();

    $hlsPath = $upload->hlsPlaylistPath();
    $segmentPath = $upload->hlsSegmentPath('segment_000.ts');

    expect($upload->status)->toBe(UploadStatus::Processing)
        ->and($upload->step_statuses[UploadStep::Hls->value])->toBe(StepStatus::Completed->value)
        ->and($upload->hls_path)->toBe($hlsPath)
        ->and(Storage::disk('s3')->exists($hlsPath))->toBeTrue()
        ->and(Storage::disk('s3')->exists($segmentPath))->toBeTrue()
        ->and(Storage::disk('s3')->get($hlsPath))->toContain('segment_000.ts');
});

test('process upload hls marks step failed when ffmpeg fails', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'step_statuses' => array_merge(Upload::defaultStepStatuses(), [
            UploadStep::Hls->value => StepStatus::Processing->value,
        ]),
        'status' => UploadStatus::Processing,
    ]);

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () {
        return Process::result(output: '', errorOutput: 'Invalid data', exitCode: 1);
    });

    $job = new ProcessUploadHls($upload->uuid);

    expect(fn () => $job->handle())
        ->toThrow(RuntimeException::class, 'ffmpeg failed to generate HLS output.');

    $job->failed(new RuntimeException('ffmpeg failed'));

    $upload->refresh();

    expect($upload->status)->toBe(UploadStatus::Failed)
        ->and($upload->step_statuses[UploadStep::Hls->value])->toBe(StepStatus::Failed->value)
        ->and($upload->hls_path)->toBeNull();
});

test('process upload hls failed handler does not throw when upload is missing', function () {
    $upload = Upload::factory()->create();
    $uuid = $upload->uuid;

    $upload->delete();

    expect(fn () => (new ProcessUploadHls($uuid))->failed(new RuntimeException('ffmpeg failed')))
        ->not->toThrow(RuntimeException::class);
});

test('process upload hls fails when no files are generated', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'step_statuses' => array_merge(Upload::defaultStepStatuses(), [
            UploadStep::Hls->value => StepStatus::Processing->value,
        ]),
        'status' => UploadStatus::Processing,
    ]);

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(fn () => Process::result());

    $job = new ProcessUploadHls($upload->uuid);

    expect(fn () => $job->handle())
        ->toThrow(RuntimeException::class, 'HLS playlist was not generated locally.');

    $job->failed(new RuntimeException('HLS playlist was not generated locally.'));

    $upload->refresh();

    expect($upload->status)->toBe(UploadStatus::Failed)
        ->and($upload->step_statuses[UploadStep::Hls->value])->toBe(StepStatus::Failed->value)
        ->and($upload->hls_path)->toBeNull();
});

test('process upload hls fails when playlist is missing but segments are present', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'step_statuses' => array_merge(Upload::defaultStepStatuses(), [
            UploadStep::Hls->value => StepStatus::Processing->value,
        ]),
        'status' => UploadStatus::Processing,
    ]);

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () use ($upload) {
        $directory = resolveHlsTempDirectory($upload);
        file_put_contents($directory.'/segment_000.ts', 'segment-bytes');

        return Process::result();
    });

    $job = new ProcessUploadHls($upload->uuid);

    expect(fn () => $job->handle())
        ->toThrow(RuntimeException::class, 'HLS playlist was not generated locally.');

    $job->failed(new RuntimeException('HLS playlist was not generated locally.'));

    $upload->refresh();

    expect($upload->status)->toBe(UploadStatus::Failed)
        ->and($upload->step_statuses[UploadStep::Hls->value])->toBe(StepStatus::Failed->value);
});

test('process upload hls removes stale existing hls files before publishing', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();

    Storage::disk('s3')->put($upload->path, 'audio-bytes');
    Storage::disk('s3')->put($upload->hlsSegmentPath('stale_segment.ts'), 'stale-bytes');
    Storage::disk('s3')->put($upload->hlsPlaylistPath(), "#EXTM3U\nstale_segment.ts\n");

    Process::fake(function () use ($upload) {
        seedFakeHlsOutput($upload);

        return Process::result();
    });

    (new ProcessUploadHls($upload->uuid))->handle();

    expect(Storage::disk('s3')->exists($upload->hlsSegmentPath('stale_segment.ts')))->toBeFalse()
        ->and(Storage::disk('s3')->exists($upload->hlsSegmentPath('segment_000.ts')))->toBeTrue();
});

test('process upload hls cleans up temp directory after failure', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'step_statuses' => array_merge(Upload::defaultStepStatuses(), [
            UploadStep::Hls->value => StepStatus::Processing->value,
        ]),
        'status' => UploadStatus::Processing,
    ]);

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () use ($upload) {
        seedFakeHlsOutput($upload, playlist: "#EXTINF:10.0,\nsegment_000.ts\n");

        return Process::result();
    });

    $job = new ProcessUploadHls($upload->uuid);

    expect(fn () => $job->handle())
        ->toThrow(RuntimeException::class, 'HLS playlist is missing #EXTM3U header.');

    expect(glob(sys_get_temp_dir().'/hls_'.$upload->uuid.'_*') ?: [])->toBe([]);
});

test('process upload hls uses exponential backoff delays', function () {
    $job = new ProcessUploadHls('test-uuid');

    expect($job->backoff())->toBe([60, 300]);
});

test('process upload hls has queue timeout above process timeout', function () {
    $job = new ProcessUploadHls('test-uuid');

    expect($job->timeout)->toBe(660);
});

function resolveHlsTempDirectory(Upload $upload): string
{
    $directories = glob(sys_get_temp_dir().'/hls_'.$upload->uuid.'_*') ?: [];

    if ($directories !== []) {
        return $directories[0];
    }

    $directory = sys_get_temp_dir().'/hls_'.$upload->uuid.'_test';

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    return $directory;
}

function seedFakeHlsOutput(Upload $upload, ?string $playlist = null): void
{
    $directory = resolveHlsTempDirectory($upload);

    file_put_contents(
        $directory.'/playlist.m3u8',
        $playlist ?? "#EXTM3U\n#EXT-X-VERSION:3\n#EXTINF:10.0,\nsegment_000.ts\n#EXT-X-ENDLIST\n",
    );

    file_put_contents($directory.'/segment_000.ts', 'segment-bytes');
}
