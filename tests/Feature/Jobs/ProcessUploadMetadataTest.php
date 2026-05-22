<?php

use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Jobs\ProcessUploadMetadata;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

test('process upload metadata extracts ffprobe data and marks step completed', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () {
        return Process::result(output: sampleFfprobeOutput());
    });

    (new ProcessUploadMetadata($upload))->handle();

    $upload->refresh();

    expect($upload->status)->toBe(UploadStatus::Processing)
        ->and($upload->step_statuses[UploadStep::Metadata->value])->toBe(StepStatus::Completed->value);

    $this->assertDatabaseHas('upload_metadata', [
        'upload_id' => $upload->id,
        'duration_seconds' => 5110.24,
        'duration' => '01:25:10',
        'container_format' => 'mp3',
        'bitrate' => 128_000,
        'codec' => 'mp3',
        'codec_long_name' => 'MP3 (MPEG audio layer 3)',
        'sample_rate' => 44_100,
        'channels' => 2,
        'channel_layout' => 'stereo',
    ]);

    $metadata = $upload->metadata;

    expect($metadata)->not->toBeNull()
        ->and($metadata->tags['title'])->toBe('Episode 1')
        ->and($metadata->tags['artist'])->toBe('Creator Name')
        ->and($metadata->cover_art['exists'])->toBeFalse()
        ->and($metadata->validation['is_playable'])->toBeTrue()
        ->and($metadata->validation['has_audio_stream'])->toBeTrue()
        ->and($metadata->validation['has_video_stream'])->toBeFalse();
});

test('process upload metadata marks step failed when ffprobe fails', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'step_statuses' => array_merge(Upload::defaultStepStatuses(), [
            UploadStep::Metadata->value => StepStatus::Processing->value,
        ]),
        'status' => UploadStatus::Processing,
    ]);

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () {
        return Process::result(output: '', errorOutput: 'Invalid data', exitCode: 1);
    });

    $job = new ProcessUploadMetadata($upload);

    try {
        $job->handle();
    } catch (Throwable) {
        $job->failed(new RuntimeException('ffprobe failed'));
    }

    $upload->refresh();

    expect($upload->status)->toBe(UploadStatus::Failed)
        ->and($upload->step_statuses[UploadStep::Metadata->value])->toBe(StepStatus::Failed->value);
});

function sampleFfprobeOutput(): string
{
    return json_encode([
        'format' => [
            'filename' => 'episode.mp3',
            'nb_streams' => 1,
            'format_name' => 'mp3',
            'duration' => '5110.24',
            'size' => '42123812',
            'bit_rate' => '128000',
            'start_time' => '0.025057',
            'tags' => [
                'title' => 'Episode 1',
                'artist' => 'Creator Name',
            ],
        ],
        'streams' => [
            [
                'codec_name' => 'mp3',
                'codec_type' => 'audio',
                'codec_long_name' => 'MP3 (MPEG audio layer 3)',
                'sample_rate' => '44100',
                'channels' => 2,
                'channel_layout' => 'stereo',
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}
