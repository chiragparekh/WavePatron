<?php

namespace App\Models;

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
    'mime_type',
    'size',
    'disk',
    'path',
    'waveform_path',
    'status',
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
