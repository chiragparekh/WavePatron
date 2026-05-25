<?php

declare(strict_types=1);

namespace App\Queries\Upload;

use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Enums\UploadStatus;
use App\Models\Subscription;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListenerAccessibleUploadsQuery
{
    public function __construct(
        private User $listener,
    ) {}

    /**
     * @return Builder<Upload>
     */
    public function builder(): Builder
    {
        $accessibleCreatorProfileIds = Subscription::query()
            ->where('user_id', $this->listener->id)
            ->get()
            ->filter(fn (Subscription $subscription): bool => $subscription->isAccessible())
            ->pluck('creator_profile_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Upload::query()
            ->with(['metadata', 'user.creatorProfile'])
            ->where('status', UploadStatus::Ready)
            ->where('publish_status', AudioPublishStatus::Published)
            ->where(function (Builder $query) use ($accessibleCreatorProfileIds): void {
                $query->where('access_level', AudioAccessLevel::Free);

                if ($accessibleCreatorProfileIds !== []) {
                    $query->orWhere(function (Builder $premiumQuery) use ($accessibleCreatorProfileIds): void {
                        $premiumQuery
                            ->where('access_level', AudioAccessLevel::Premium)
                            ->whereHas('user.creatorProfile', fn (Builder $profileQuery) => $profileQuery
                                ->whereIn('id', $accessibleCreatorProfileIds));
                    });
                }
            })
            ->latest('uploaded_at');
    }
}
