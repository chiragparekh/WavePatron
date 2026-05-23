<?php

namespace App\Jobs;

use App\Enums\UploadStep;
use App\Jobs\Concerns\InteractsWithUploadStep;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessUploadWaveform implements ShouldQueue
{
    use InteractsWithUploadStep;
    use Queueable;

    private const int PeakCount = 1000;

    public function __construct(
        public string $uploadUuid,
    ) {}

    public function handle(): void
    {
        $upload = $this->resolveUpload();

        Log::info('Starting upload waveform generation.', $this->logContext($upload));

        $this->markStepProcessing($upload);

        $url = Storage::disk($upload->disk)->temporaryUrl(
            $upload->path,
            now()->addMinutes(10),
        );

        Log::debug('Running ffmpeg for upload waveform.', $this->logContext($upload, [
            'disk' => $upload->disk,
            'path' => $upload->path,
        ]));

        $result = Process::run([
            'ffmpeg',
            '-i', $url,
            '-ac', '1',
            '-ar', '8000',
            '-f', 's16le',
            '-acodec', 'pcm_s16le',
            'pipe:1',
        ]);

        if (! $result->successful()) {
            Log::error('ffmpeg failed to extract upload waveform.', $this->logContext($upload, [
                'exit_code' => $result->exitCode(),
                'error_output' => $result->errorOutput(),
            ]));

            throw new RuntimeException($result->errorOutput() ?: 'ffmpeg failed to extract waveform.');
        }

        $peaks = $this->generatePeaks($result->output());

        $waveformPath = "waveforms/{$upload->uuid}.json";

        Storage::disk($upload->disk)->put($waveformPath, json_encode([
            'version' => 1,
            'length' => self::PeakCount,
            'data' => $peaks,
        ], JSON_THROW_ON_ERROR));

        $upload->update(['waveform_path' => $waveformPath]);

        $this->markStepCompleted($upload);

        Log::info('Upload waveform generation completed.', $this->logContext($upload, [
            'waveform_path' => $waveformPath,
            'peak_count' => count($peaks),
        ]));
    }

    public function failed(?Throwable $exception): void
    {
        $this->safelyHandleFailure($exception, 'Upload waveform generation failed.');
    }

    protected function uploadStep(): UploadStep
    {
        return UploadStep::Waveform;
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    protected function generatePeaks(string $pcmData): array
    {
        $sampleCount = intdiv(strlen($pcmData), 2);

        if ($sampleCount === 0) {
            throw new RuntimeException('No PCM samples extracted from audio.');
        }

        /** @var array<int, int> $samples */
        $samples = array_values(unpack('s*', $pcmData));

        $chunkSize = max(1, intdiv($sampleCount, self::PeakCount));
        $peaks = [];

        for ($index = 0; $index < self::PeakCount; $index++) {
            $start = $index * $chunkSize;

            if ($start >= $sampleCount) {
                $peaks[] = [0.0, 0.0];

                continue;
            }

            $end = min($start + $chunkSize, $sampleCount);
            $chunk = array_slice($samples, $start, $end - $start);

            $min = min($chunk);
            $max = max($chunk);

            $peaks[] = [
                round($min / 32768, 4),
                round($max / 32768, 4),
            ];
        }

        return $peaks;
    }
}
