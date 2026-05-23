<?php

namespace App\Jobs;

use App\Enums\UploadStep;
use App\Jobs\Concerns\InteractsWithUploadStep;
use App\Models\Upload;
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

    private const int ProcessTimeoutSeconds = 300;

    private const string TempFilePrefix = 'waveform_';

    public function __construct(
        public string $uploadUuid,
    ) {}

    public function handle(): void
    {
        $upload = $this->resolveUpload();
        $tempPath = $this->createTempFile($upload);

        Log::info('Starting upload waveform generation.', $this->logContext($upload));

        $this->markStepProcessing($upload);

        try {
            $this->runFfmpeg($upload, $tempPath);

            $peaks = $this->generatePeaksFromFile($tempPath);

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
        } finally {
            $this->safelyCleanupTempFile($tempPath);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->safelyHandleFailure($exception, 'Upload waveform generation failed.');
    }

    protected function uploadStep(): UploadStep
    {
        return UploadStep::Waveform;
    }

    protected function createTempFile(Upload $upload): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), self::TempFilePrefix.$upload->uuid.'_');

        if ($tempPath === false) {
            throw new RuntimeException('Failed to create temporary waveform file.');
        }

        return $tempPath;
    }

    protected function runFfmpeg(Upload $upload, string $tempPath): void
    {
        $url = Storage::disk($upload->disk)->temporaryUrl(
            $upload->path,
            now()->addMinutes(10),
        );

        Log::debug('Running ffmpeg for upload waveform.', $this->logContext($upload, [
            'disk' => $upload->disk,
            'path' => $upload->path,
            'temp_path' => $tempPath,
        ]));

        $result = Process::timeout(self::ProcessTimeoutSeconds)->run([
            'ffmpeg',
            '-nostdin',
            '-hide_banner',
            '-y',
            '-i', $url,
            '-ac', '1',
            '-ar', '8000',
            '-f', 's16le',
            '-acodec', 'pcm_s16le',
            $tempPath,
        ]);

        if (! $result->successful()) {
            Log::error('ffmpeg failed to extract upload waveform.', $this->logContext($upload, [
                'exit_code' => $result->exitCode(),
                'error_output' => $result->errorOutput(),
            ]));

            throw new RuntimeException($result->errorOutput() ?: 'ffmpeg failed to extract waveform.');
        }
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    protected function generatePeaksFromFile(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open extracted PCM file.');
        }

        try {
            $fileSize = filesize($path);

            if ($fileSize === false || $fileSize < 2) {
                throw new RuntimeException('No PCM samples extracted from audio.');
            }

            $sampleCount = intdiv($fileSize, 2);
            $chunkSize = max(1, intdiv($sampleCount, self::PeakCount));
            $peaks = [];

            for ($index = 0; $index < self::PeakCount; $index++) {
                $startSample = $index * $chunkSize;

                if ($startSample >= $sampleCount) {
                    $peaks[] = [0.0, 0.0];

                    continue;
                }

                $samplesInChunk = min($chunkSize, $sampleCount - $startSample);
                $byteOffset = $startSample * 2;
                $byteLength = $samplesInChunk * 2;

                if (fseek($handle, $byteOffset) !== 0) {
                    $peaks[] = [0.0, 0.0];

                    continue;
                }

                $chunk = fread($handle, $byteLength);

                if ($chunk === false || strlen($chunk) < 2) {
                    $peaks[] = [0.0, 0.0];

                    continue;
                }

                /** @var array<int, int> $samples */
                $samples = array_values(unpack('s*', $chunk));

                $peaks[] = [
                    round(min($samples) / 32768, 4),
                    round(max($samples) / 32768, 4),
                ];
            }

            return $peaks;
        } finally {
            fclose($handle);
        }
    }

    protected function safelyCleanupTempFile(string $tempPath): void
    {
        try {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to clean up temporary waveform file.', [
                'upload_uuid' => $this->uploadUuid,
                'temp_path' => $tempPath,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
