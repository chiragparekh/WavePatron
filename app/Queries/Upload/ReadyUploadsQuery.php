<?php

declare(strict_types=1);

namespace App\Queries\Upload;

use App\Enums\UploadStatus;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final readonly class ReadyUploadsQuery
{
    public function __construct(
        private User $user,
    ) {}

    /**
     * @return Builder<Upload>
     */
    public function builder(): Builder
    {
        return Upload::query()
            ->whereBelongsTo($this->user)
            ->with('metadata')
            ->where('status', UploadStatus::Ready)
            ->latest('uploaded_at');
    }
}
