<?php

namespace App\Actions\Upload;

use App\Enums\UploadStatus;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InitiateUpload
{
    /**
     * @param  array{name: string, size: int, type: string}  $metadata
     * @return array{uuid: string, url: string, headers: array<string, string>, path: string, expires_at: string}
     */
    public function execute(User $user, array $metadata): array
    {
        $uuid = (string) Str::uuid7();
        $extension = $this->resolveExtension($metadata['name'], $metadata['type']);
        $path = sprintf(
            'uploads/%s/%s.%s',
            $user->id,
            $uuid,
            $extension,
        );

        Upload::query()->create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'original_name' => $metadata['name'],
            'mime_type' => $metadata['type'],
            'size' => $metadata['size'],
            'disk' => 's3',
            'path' => $path,
            'status' => UploadStatus::PendingUpload,
            'step_statuses' => Upload::defaultStepStatuses(),
        ]);

        $expiresAt = now()->addMinutes(10);

        $signed = Storage::disk('s3')->temporaryUploadUrl(
            $path,
            $expiresAt,
            ['ContentType' => $metadata['type']],
        );

        return [
            'uuid' => $uuid,
            'url' => $signed['url'],
            'headers' => $this->normalizeSignedHeaders($signed['headers']),
            'path' => $path,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function resolveExtension(string $name, string $mime): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($extension !== '' && preg_match('/^[a-z0-9]+$/', $extension) === 1) {
            return $extension;
        }

        return match ($mime) {
            'audio/mpeg' => 'mp3',
            'audio/wav', 'audio/wave', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/flac' => 'flac',
            'audio/aac' => 'aac',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            'audio/webm' => 'webm',
            default => 'bin',
        };
    }

    /**
     * @param  array<string, array<int, string>|string>  $headers
     * @return array<string, string>
     */
    private function normalizeSignedHeaders(array $headers): array
    {
        $forbidden = ['host', 'connection', 'content-length'];

        return collect($headers)
            ->mapWithKeys(function (array|string $value, string $key): array {
                $normalizedKey = strtolower($key);

                if (is_array($value)) {
                    return [$normalizedKey => $value[0] ?? ''];
                }

                return [$normalizedKey => $value];
            })
            ->reject(fn (string $value, string $key): bool => in_array($key, $forbidden, true) || $value === '')
            ->all();
    }
}
