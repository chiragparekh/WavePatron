<?php

namespace App\Http\Resources;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Upload */
class CreatorAudioResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'original_name' => $this->original_name,
            'display_title' => $this->displayTitle(),
            'status' => $this->status->value,
            'publish_status' => $this->publish_status->value,
            'access_level' => $this->access_level->value,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'can_publish' => $this->isReady(),
            'metadata' => $this->whenLoaded('metadata', fn (): ?array => $this->metadata === null ? null : [
                'title' => $this->metadata->tags['title'] ?? null,
                'artist' => $this->metadata->tags['artist'] ?? null,
                'duration' => $this->metadata->duration,
            ]),
            'hls_playlist_url' => $this->isReady()
                ? route('uploads.hls.playlist', $this)
                : null,
            'waveform_url' => $this->isReady()
                ? route('uploads.waveform', $this)
                : null,
        ];
    }
}
