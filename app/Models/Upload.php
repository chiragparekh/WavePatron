<?php

namespace App\Models;

use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Policies\UploadPolicy;
use Database\Factories\UploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'uuid',
    'user_id',
    'original_name',
    'title',
    'description',
    'mime_type',
    'size',
    'disk',
    'path',
    'waveform_path',
    'hls_path',
    'status',
    'publish_status',
    'access_level',
    'step_statuses',
    'uploaded_at',
])]
#[Hidden(['id'])]
#[UsePolicy(UploadPolicy::class)]
class Upload extends Model
{
    /** @use HasFactory<UploadFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UploadStatus::class,
            'publish_status' => AudioPublishStatus::class,
            'access_level' => AudioAccessLevel::class,
            'step_statuses' => 'array',
            'uploaded_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    public static function defaultStepStatuses(): array
    {
        return collect(UploadStep::cases())
            ->mapWithKeys(fn (UploadStep $step): array => [
                $step->value => StepStatus::Pending->value,
            ])
            ->all();
    }

    public function isPublished(): bool
    {
        return $this->publish_status === AudioPublishStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->publish_status === AudioPublishStatus::Draft;
    }

    public function isFree(): bool
    {
        return $this->access_level === AudioAccessLevel::Free;
    }

    public function isPremium(): bool
    {
        return $this->access_level === AudioAccessLevel::Premium;
    }

    public function isReady(): bool
    {
        return $this->status === UploadStatus::Ready;
    }

    public function displayTitle(): string
    {
        return $this->title ?? $this->metadata?->tags['title'] ?? $this->original_name;
    }

    public function hlsStoragePrefix(): string
    {
        return "hls/{$this->uuid}";
    }

    public function hlsPlaylistPath(): string
    {
        return "{$this->hlsStoragePrefix()}/playlist.m3u8";
    }

    public function hlsSegmentPath(string $segment): string
    {
        return "{$this->hlsStoragePrefix()}/{$segment}";
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<UploadMetadata, $this>
     */
    public function metadata(): HasOne
    {
        return $this->hasOne(UploadMetadata::class);
    }
}
