<?php

namespace App\Models;

use Database\Factories\UploadMetadataFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'upload_id',
    'duration_seconds',
    'duration',
    'start_time',
    'container_format',
    'bitrate',
    'codec',
    'codec_long_name',
    'sample_rate',
    'channels',
    'channel_layout',
    'tags',
    'cover_art',
    'validation',
])]
class UploadMetadata extends Model
{
    /** @use HasFactory<UploadMetadataFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'float',
            'start_time' => 'float',
            'bitrate' => 'integer',
            'sample_rate' => 'integer',
            'channels' => 'integer',
            'tags' => 'array',
            'cover_art' => 'array',
            'validation' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Upload, $this>
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
}
