<?php

declare(strict_types=1);

namespace App\Queries\Upload;

use App\Enums\UploadStatus;
use App\Models\Upload;
use App\Models\User;

final readonly class UploadStatsQuery
{
    public function __construct(
        private User $user,
    ) {}

    /**
     * @return array{total_ready: int, total_processing: int, total_failed: int, total_storage_bytes: int}
     */
    public function get(): array
    {
        $stats = Upload::query()
            ->whereBelongsTo($this->user)
            ->selectRaw('
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_ready,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_processing,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_failed,
                COALESCE(SUM(size), 0) as total_storage_bytes
            ', [
                UploadStatus::Ready->value,
                UploadStatus::Processing->value,
                UploadStatus::Failed->value,
            ])
            ->first();

        return [
            'total_ready' => (int) ($stats->total_ready ?? 0),
            'total_processing' => (int) ($stats->total_processing ?? 0),
            'total_failed' => (int) ($stats->total_failed ?? 0),
            'total_storage_bytes' => (int) ($stats->total_storage_bytes ?? 0),
        ];
    }
}
