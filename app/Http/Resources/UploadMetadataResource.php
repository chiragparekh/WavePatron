<?php

namespace App\Http\Resources;

use App\Models\UploadMetadata;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UploadMetadata */
class UploadMetadataResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'duration_seconds' => $this->duration_seconds,
            'duration' => $this->duration,
            'start_time' => $this->start_time,
            'container_format' => $this->container_format,
            'bitrate' => $this->bitrate,
            'codec' => $this->codec,
            'codec_long_name' => $this->codec_long_name,
            'sample_rate' => $this->sample_rate,
            'channels' => $this->channels,
            'channel_layout' => $this->channel_layout,
            'title' => $this->tags['title'] ?? null,
            'artist' => $this->tags['artist'] ?? null,
            'album' => $this->tags['album'] ?? null,
            'genre' => $this->tags['genre'] ?? null,
            'track' => $this->tags['track'] ?? null,
            'disc' => $this->tags['disc'] ?? null,
            'date' => $this->tags['date'] ?? null,
            'comment' => $this->tags['comment'] ?? null,
            'composer' => $this->tags['composer'] ?? null,
            'publisher' => $this->tags['publisher'] ?? null,
            'copyright' => $this->tags['copyright'] ?? null,
            'cover_art_exists' => $this->cover_art['exists'] ?? false,
            'cover_art_format' => $this->cover_art['format'] ?? null,
            'cover_art_width' => $this->cover_art['width'] ?? null,
            'cover_art_height' => $this->cover_art['height'] ?? null,
            'is_playable' => $this->validation['is_playable'] ?? false,
            'has_audio_stream' => $this->validation['has_audio_stream'] ?? false,
            'has_video_stream' => $this->validation['has_video_stream'] ?? false,
        ];
    }
}
