<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{
 *     uuid: string,
 *     url: string,
 *     headers: array<string, string>,
 *     path: string,
 *     expires_at: string,
 * } $resource
 */
class SignedUploadResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource['uuid'],
            'url' => $this->resource['url'],
            'headers' => $this->resource['headers'],
            'path' => $this->resource['path'],
            'expires_at' => $this->resource['expires_at'],
        ];
    }
}
