<?php

namespace App\Jobs;

use App\Enums\UploadStep;
use App\Jobs\Concerns\InteractsWithUploadStep;
use App\Models\Upload;
use App\Models\UploadMetadata;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessUploadMetadata implements ShouldQueue
{
    use InteractsWithUploadStep;
    use Queueable;

    public function __construct(
        public Upload $upload,
    ) {}

    public function handle(): void
    {
        Log::info('Starting upload metadata extraction.', $this->logContext());

        $this->markStepProcessing($this->upload);

        $url = Storage::disk($this->upload->disk)->temporaryUrl(
            $this->upload->path,
            now()->addMinutes(10),
        );

        Log::debug('Running ffprobe for upload metadata.', $this->logContext([
            'disk' => $this->upload->disk,
            'path' => $this->upload->path,
        ]));

        $result = Process::run([
            'ffprobe',
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $url,
        ]);

        if (! $result->successful()) {
            Log::error('ffprobe failed to extract upload metadata.', $this->logContext([
                'exit_code' => $result->exitCode(),
                'error_output' => $result->errorOutput(),
            ]));

            throw new RuntimeException($result->errorOutput() ?: 'ffprobe failed to extract metadata.');
        }

        $probe = json_decode($result->output(), true);

        if (! is_array($probe)) {
            Log::error('ffprobe returned invalid JSON for upload metadata.', $this->logContext([
                'output' => $result->output(),
            ]));

            throw new RuntimeException('ffprobe returned invalid JSON.');
        }

        $audioStream = collect($probe['streams'] ?? [])
            ->first(fn (array $stream): bool => ($stream['codec_type'] ?? null) === 'audio');

        if ($audioStream === null) {
            Log::error('No audio stream found in uploaded file.', $this->logContext([
                'stream_count' => count($probe['streams'] ?? []),
            ]));

            throw new RuntimeException('No audio stream found in uploaded file.');
        }

        $format = $probe['format'] ?? [];
        $tags = $this->normalizeTags($format['tags'] ?? []);
        $coverArtStream = collect($probe['streams'] ?? [])
            ->first(fn (array $stream): bool => ($stream['disposition']['attached_pic'] ?? 0) == 1);

        $metadata = UploadMetadata::query()->updateOrCreate(
            ['upload_id' => $this->upload->id],
            [
                'duration_seconds' => isset($format['duration']) ? (float) $format['duration'] : null,
                'duration' => isset($format['duration']) ? $this->formatDuration((float) $format['duration']) : null,
                'start_time' => isset($format['start_time']) ? (float) $format['start_time'] : null,
                'container_format' => $format['format_name'] ?? null,
                'bitrate' => isset($format['bit_rate']) ? (int) $format['bit_rate'] : null,
                'codec' => $audioStream['codec_name'] ?? null,
                'codec_long_name' => $audioStream['codec_long_name'] ?? null,
                'sample_rate' => isset($audioStream['sample_rate']) ? (int) $audioStream['sample_rate'] : null,
                'channels' => isset($audioStream['channels']) ? (int) $audioStream['channels'] : null,
                'channel_layout' => $audioStream['channel_layout'] ?? null,
                'tags' => $tags,
                'cover_art' => [
                    'exists' => $coverArtStream !== null,
                    'format' => $coverArtStream['codec_name'] ?? null,
                    'width' => isset($coverArtStream['width']) ? (int) $coverArtStream['width'] : null,
                    'height' => isset($coverArtStream['height']) ? (int) $coverArtStream['height'] : null,
                ],
                'validation' => [
                    'is_playable' => true,
                    'has_audio_stream' => true,
                    'has_video_stream' => collect($probe['streams'] ?? [])
                        ->contains(fn (array $stream): bool => ($stream['codec_type'] ?? null) === 'video'),
                ],
            ],
        );

        $this->markStepCompleted($this->upload);

        Log::info('Upload metadata extraction completed.', $this->logContext([
            'metadata_id' => $metadata->id,
            'duration_seconds' => $metadata->duration_seconds,
            'codec' => $metadata->codec,
            'container_format' => $metadata->container_format,
            'bitrate' => $metadata->bitrate,
        ]));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Upload metadata extraction failed.', $this->logContext([
            'exception' => $exception?->getMessage(),
        ]));

        $this->markStepFailed($this->upload);
    }

    protected function uploadStep(): UploadStep
    {
        return UploadStep::Metadata;
    }

    /**
     * @param  array<string, mixed>  $rawTags
     * @return array<string, string|null>
     */
    protected function normalizeTags(array $rawTags): array
    {
        $keys = [
            'title',
            'artist',
            'album',
            'genre',
            'track',
            'disc',
            'date',
            'comment',
            'composer',
            'publisher',
            'copyright',
        ];

        return collect($keys)
            ->mapWithKeys(function (string $key) use ($rawTags): array {
                $value = $rawTags[$key] ?? null;

                return [$key => is_scalar($value) ? (string) $value : null];
            })
            ->all();
    }

    protected function formatDuration(float $seconds): string
    {
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $remainingSeconds = (int) floor($seconds % 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function logContext(array $context = []): array
    {
        return array_merge([
            'upload_id' => $this->upload->id,
            'upload_uuid' => $this->upload->uuid,
            'user_id' => $this->upload->user_id,
            'step' => $this->uploadStep()->value,
        ], $context);
    }
}
