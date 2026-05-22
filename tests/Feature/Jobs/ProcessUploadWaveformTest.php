<?php

use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Jobs\ProcessUploadWaveform;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

test('process upload waveform extracts peaks and marks step completed', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create();

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () {
        return Process::result(output: samplePcmOutput());
    });

    (new ProcessUploadWaveform($upload->uuid))->handle();

    $upload->refresh();

    $waveformPath = "waveforms/{$upload->uuid}.json";

    expect($upload->status)->toBe(UploadStatus::Processing)
        ->and($upload->step_statuses[UploadStep::Waveform->value])->toBe(StepStatus::Completed->value)
        ->and($upload->waveform_path)->toBe($waveformPath)
        ->and(Storage::disk('s3')->exists($waveformPath))->toBeTrue();

    $waveform = json_decode(Storage::disk('s3')->get($waveformPath), true, flags: JSON_THROW_ON_ERROR);

    expect($waveform)->toHaveKeys(['version', 'length', 'data'])
        ->and($waveform['version'])->toBe(1)
        ->and($waveform['length'])->toBe(1000)
        ->and($waveform['data'])->toHaveCount(1000)
        ->and($waveform['data'][0])->toBeArray()
        ->and($waveform['data'][0])->toHaveCount(2)
        ->and($waveform['data'][0][0])->toBeNumeric()
        ->and($waveform['data'][0][1])->toBeNumeric()
        ->and($waveform['data'][0][0])->toBeGreaterThanOrEqual(-1)
        ->and($waveform['data'][0][1])->toBeLessThanOrEqual(1);
});

test('process upload waveform marks step failed when ffmpeg fails', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->uploaded()->create([
        'step_statuses' => array_merge(Upload::defaultStepStatuses(), [
            UploadStep::Waveform->value => StepStatus::Processing->value,
        ]),
        'status' => UploadStatus::Processing,
    ]);

    Storage::disk('s3')->put($upload->path, 'audio-bytes');

    Process::fake(function () {
        return Process::result(output: '', errorOutput: 'Invalid data', exitCode: 1);
    });

    $job = new ProcessUploadWaveform($upload->uuid);

    try {
        $job->handle();
    } catch (Throwable) {
        $job->failed(new RuntimeException('ffmpeg failed'));
    }

    $upload->refresh();

    expect($upload->status)->toBe(UploadStatus::Failed)
        ->and($upload->step_statuses[UploadStep::Waveform->value])->toBe(StepStatus::Failed->value)
        ->and($upload->waveform_path)->toBeNull();
});

function samplePcmOutput(): string
{
    $bytes = '';

    for ($index = 0; $index < 8000; $index++) {
        $sample = (int) (16000 * sin($index / 100));
        $bytes .= pack('s', $sample);
    }

    return $bytes;
}
