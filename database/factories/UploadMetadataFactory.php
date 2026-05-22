<?php

namespace Database\Factories;

use App\Models\Upload;
use App\Models\UploadMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UploadMetadata>
 */
class UploadMetadataFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'upload_id' => Upload::factory(),
            'duration_seconds' => 5110.24,
            'duration' => '01:25:10',
            'start_time' => 0.025057,
            'container_format' => 'mp3',
            'bitrate' => 128_000,
            'codec' => 'mp3',
            'codec_long_name' => 'MP3 (MPEG audio layer 3)',
            'sample_rate' => 44_100,
            'channels' => 2,
            'channel_layout' => 'stereo',
            'tags' => [
                'title' => 'Episode 10',
                'artist' => 'John Doe',
                'album' => 'Season 1',
                'genre' => 'Technology',
                'track' => '10',
                'disc' => '1',
                'date' => '2026',
                'comment' => 'Sample comment',
                'composer' => 'Jane Composer',
                'publisher' => 'Example Publisher',
                'copyright' => '2026 Example',
            ],
            'cover_art' => [
                'exists' => false,
                'format' => null,
                'width' => null,
                'height' => null,
            ],
            'validation' => [
                'is_playable' => true,
                'has_audio_stream' => true,
                'has_video_stream' => false,
            ],
        ];
    }
}
