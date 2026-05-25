<?php

declare(strict_types=1);

namespace App\Queries\Upload;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final readonly class CreatorUploadsQuery
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
            ->latest('uploaded_at')
            ->latest('created_at');
    }
}
