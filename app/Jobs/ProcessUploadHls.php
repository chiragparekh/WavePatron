<?php

namespace App\Jobs;

use App\Enums\UploadStep;
use App\Jobs\Concerns\InteractsWithUploadStep;
use App\Models\Upload;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessUploadHls implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithUploadStep;
    use Queueable;

    private const int SegmentDurationSeconds = 10;

    private const string AacBitrate = '128k';

    private const int ProcessTimeoutSeconds = 600;

    private const int QueueTimeoutSeconds = 660;

    private const string TempDirectoryPrefix = 'hls_';

    private const string PlaylistFilename = 'playlist.m3u8';

    private const string SegmentFilenamePattern = 'segment_%03d.ts';

    public int $timeout = self::QueueTimeoutSeconds;

    public int $tries = 2;

    public function __construct(
        public string $uploadUuid,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function uniqueId(): string
    {
        return $this->uploadUuid;
    }

    public function handle(): void
    {
        $upload = $this->resolveUpload();
        $tempDirectory = $this->createTempDirectory($upload);

        Log::info('Starting upload HLS generation.', $this->logContext($upload));

        $this->markStepProcessing($upload);

        try {
            $this->runFfmpeg($upload, $tempDirectory);

            $this->assertGeneratedPlaylistIsValid($tempDirectory);

            $this->removeExistingHlsOutput($upload);

            $uploadedFiles = $this->publishGeneratedFiles($upload, $tempDirectory);

            $this->assertPublishedPlaylistExists($upload, $uploadedFiles);

            $upload->update(['hls_path' => $upload->hlsPlaylistPath()]);

            $this->markStepCompleted($upload);

            Log::info('Upload HLS generation completed.', $this->logContext($upload, [
                'hls_path' => $upload->hlsPlaylistPath(),
                'uploaded_files' => $uploadedFiles,
            ]));
        } finally {
            $this->safelyCleanupTempDirectory($tempDirectory);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->safelyHandleFailure($exception, 'Upload HLS generation failed.');
    }

    protected function uploadStep(): UploadStep
    {
        return UploadStep::Hls;
    }

    protected function createTempDirectory(Upload $upload): string
    {
        $suffix = $this->job !== null
            ? (string) $this->job->attempts()
            : bin2hex(random_bytes(4));

        $tempDirectory = sys_get_temp_dir().'/'.self::TempDirectoryPrefix.$upload->uuid.'_'.$suffix;

        if (! is_dir($tempDirectory) && ! mkdir($tempDirectory, 0777, true) && ! is_dir($tempDirectory)) {
            throw new RuntimeException('Failed to create temporary HLS directory.');
        }

        return $tempDirectory;
    }

    protected function runFfmpeg(Upload $upload, string $tempDirectory): void
    {
        $url = Storage::disk($upload->disk)->temporaryUrl(
            $upload->path,
            now()->addMinutes(60),
        );

        Log::debug('Running ffmpeg for upload HLS generation.', $this->logContext($upload, [
            'disk' => $upload->disk,
            'path' => $upload->path,
            'temp_directory' => $tempDirectory,
        ]));

        $playlistPath = $tempDirectory.'/'.self::PlaylistFilename;
        $segmentFilename = $tempDirectory.'/'.self::SegmentFilenamePattern;

        $result = Process::timeout(self::ProcessTimeoutSeconds)->run([
            'ffmpeg',
            '-nostdin',
            '-hide_banner',
            '-vn',
            '-i', $url,
            '-c:a', 'aac',
            '-b:a', self::AacBitrate,
            '-f', 'hls',
            '-hls_time', (string) self::SegmentDurationSeconds,
            '-hls_playlist_type', 'vod',
            '-hls_segment_filename', $segmentFilename,
            $playlistPath,
        ]);

        if (! $result->successful()) {
            Log::error('ffmpeg failed to generate upload HLS output.', $this->logContext($upload, [
                'exit_code' => $result->exitCode(),
                'error_output' => $this->trimOutput($result->errorOutput()),
            ]));

            throw new RuntimeException('ffmpeg failed to generate HLS output.');
        }
    }

    protected function assertGeneratedPlaylistIsValid(string $tempDirectory): void
    {
        $playlistPath = $tempDirectory.'/'.self::PlaylistFilename;

        if (! is_file($playlistPath)) {
            throw new RuntimeException('HLS playlist was not generated locally.');
        }

        $contents = file_get_contents($playlistPath);

        if ($contents === false || $contents === '') {
            throw new RuntimeException('HLS playlist is empty.');
        }

        if (! str_contains($contents, '#EXTM3U')) {
            throw new RuntimeException('HLS playlist is missing #EXTM3U header.');
        }

        if (! str_contains($contents, '#EXT-X-ENDLIST')) {
            throw new RuntimeException('HLS playlist is missing #EXT-X-ENDLIST tag.');
        }

        $segmentFiles = glob($tempDirectory.'/segment_*.ts') ?: [];

        if ($segmentFiles === []) {
            throw new RuntimeException('No HLS segments were generated.');
        }

        $referencedSegment = false;

        foreach ($segmentFiles as $segmentFile) {
            if (str_contains($contents, basename($segmentFile))) {
                $referencedSegment = true;

                break;
            }
        }

        if (! $referencedSegment) {
            throw new RuntimeException('HLS playlist does not reference any generated segment.');
        }
    }

    protected function removeExistingHlsOutput(Upload $upload): void
    {
        $disk = Storage::disk($upload->disk);
        $prefix = $upload->hlsStoragePrefix();

        if ($disk->exists($prefix)) {
            $disk->deleteDirectory($prefix);
        }
    }

    protected function publishGeneratedFiles(Upload $upload, string $tempDirectory): int
    {
        $hlsPrefix = $upload->hlsStoragePrefix();
        $uploadedFiles = 0;

        foreach (glob($tempDirectory.'/*') ?: [] as $filePath) {
            if (! is_file($filePath)) {
                continue;
            }

            $filename = basename($filePath);
            $storagePath = "{$hlsPrefix}/{$filename}";

            $stream = fopen($filePath, 'r');

            if ($stream === false) {
                throw new RuntimeException("Failed to read generated HLS file [{$filename}].");
            }

            try {
                Storage::disk($upload->disk)->put($storagePath, $stream);
            } finally {
                fclose($stream);
            }

            $uploadedFiles++;
        }

        if ($uploadedFiles === 0) {
            throw new RuntimeException('No HLS files were generated.');
        }

        return $uploadedFiles;
    }

    protected function assertPublishedPlaylistExists(Upload $upload, int $uploadedFiles): void
    {
        if ($uploadedFiles === 0) {
            throw new RuntimeException('No HLS files were uploaded.');
        }

        $hlsPath = $upload->hlsPlaylistPath();

        if (! Storage::disk($upload->disk)->exists($hlsPath)) {
            throw new RuntimeException('HLS playlist was not uploaded to storage.');
        }
    }

    protected function safelyCleanupTempDirectory(string $tempDirectory): void
    {
        try {
            $this->cleanupTempDirectory($tempDirectory);
        } catch (Throwable $exception) {
            Log::warning('Failed to clean up temporary HLS directory.', [
                'upload_uuid' => $this->uploadUuid,
                'temp_directory' => $tempDirectory,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    protected function cleanupTempDirectory(string $tempDirectory): void
    {
        if (is_dir($tempDirectory)) {
            File::deleteDirectory($tempDirectory);
        }
    }

    protected function trimOutput(string $output, int $maxLength = 2000): string
    {
        if (strlen($output) <= $maxLength) {
            return $output;
        }

        return substr($output, 0, $maxLength).'...';
    }
}
