<?php

namespace App\Http\Resources;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Upload */
class UploadResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'step_statuses' => $this->step_statuses,
            'path' => $this->path,
            'original_name' => $this->original_name,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'metadata' => UploadMetadataResource::make($this->whenLoaded('metadata')),
        ];
    }
}
