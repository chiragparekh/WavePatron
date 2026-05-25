<?php

namespace Database\Factories;

use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Models\Upload;
use App\Models\UploadMetadata;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Upload>
 */
class UploadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid7();
        $user = User::factory();

        return [
            'uuid' => $uuid,
            'user_id' => $user,
            'original_name' => 'track.mp3',
            'mime_type' => 'audio/mpeg',
            'size' => 1_024,
            'disk' => 's3',
            'path' => fn (array $attributes): string => sprintf(
                'uploads/%s/%s.mp3',
                $attributes['user_id'] instanceof User
                    ? $attributes['user_id']->id
                    : $attributes['user_id'],
                $attributes['uuid'] ?? $uuid,
            ),
            'status' => UploadStatus::PendingUpload,
            'publish_status' => AudioPublishStatus::Draft,
            'access_level' => AudioAccessLevel::Free,
            'step_statuses' => Upload::defaultStepStatuses(),
            'uploaded_at' => null,
        ];
    }

    public function pendingUpload(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UploadStatus::PendingUpload,
            'uploaded_at' => null,
        ]);
    }

    public function uploaded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UploadStatus::Uploaded,
            'uploaded_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UploadStatus::Processing,
            'uploaded_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UploadStatus::Failed,
            'uploaded_at' => now(),
            'step_statuses' => array_merge(Upload::defaultStepStatuses(), [
                UploadStep::Metadata->value => StepStatus::Completed->value,
                UploadStep::Waveform->value => StepStatus::Failed->value,
            ]),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UploadStatus::Ready,
            'uploaded_at' => now(),
            'waveform_path' => sprintf('waveforms/%s.json', $attributes['uuid']),
            'hls_path' => sprintf('hls/%s/playlist.m3u8', $attributes['uuid']),
            'step_statuses' => collect(UploadStep::cases())
                ->mapWithKeys(fn (UploadStep $step): array => [
                    $step->value => StepStatus::Completed->value,
                ])
                ->all(),
        ]);
    }

    public function withMetadata(): static
    {
        return $this->afterCreating(function (Upload $upload): void {
            UploadMetadata::factory()->for($upload)->create();
        });
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'publish_status' => AudioPublishStatus::Published,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'publish_status' => AudioPublishStatus::Draft,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes): array => [
            'access_level' => AudioAccessLevel::Premium,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes): array => [
            'access_level' => AudioAccessLevel::Free,
        ]);
    }
}
