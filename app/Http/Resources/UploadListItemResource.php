<?php

namespace App\Http\Resources;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Upload */
class UploadListItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'original_name' => $this->original_name,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'metadata' => $this->whenLoaded('metadata', fn (): ?array => $this->metadata === null ? null : [
                'title' => $this->metadata->tags['title'] ?? null,
                'artist' => $this->metadata->tags['artist'] ?? null,
                'duration' => $this->metadata->duration,
                'duration_seconds' => $this->metadata->duration_seconds,
                'codec' => $this->metadata->codec,
                'bitrate' => $this->metadata->bitrate,
                'sample_rate' => $this->metadata->sample_rate,
            ]),
            'hls_playlist_url' => route('uploads.hls.playlist', $this),
            'waveform_url' => route('uploads.waveform', $this),
        ];
    }
}
