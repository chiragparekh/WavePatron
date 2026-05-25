<?php

namespace App\Http\Resources;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Upload */
class CreatorAudioListItemResource extends JsonResource
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
            'original_name' => $this->original_name,
            'display_title' => $this->displayTitle(),
            'status' => $this->status->value,
            'publish_status' => $this->publish_status->value,
            'access_level' => $this->access_level->value,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'can_publish' => $this->isReady(),
        ];
    }
}
