<?php

namespace App\Models;

use App\Enums\CreatorPayoutStatus;
use App\Enums\CreatorProfileVisibility;
use App\Policies\CreatorProfilePolicy;
use Database\Factories\CreatorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id',
    'handle',
    'display_name',
    'tagline',
    'bio',
    'avatar_path',
    'cover_image_path',
    'categories',
    'website',
    'social_links',
    'support_email',
    'visibility',
    'stripe_connect_account_id',
    'payout_status',
])]
#[UsePolicy(CreatorProfilePolicy::class)]
class CreatorProfile extends Model
{
    /** @use HasFactory<CreatorProfileFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => CreatorProfileVisibility::class,
            'payout_status' => CreatorPayoutStatus::class,
            'categories' => 'array',
            'social_links' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    public function isPubliclyVisible(): bool
    {
        return $this->visibility === CreatorProfileVisibility::Public;
    }

    public function payoutIsEnabled(): bool
    {
        return $this->payout_status === CreatorPayoutStatus::Enabled;
    }

    public function payoutIsRestricted(): bool
    {
        return $this->payout_status === CreatorPayoutStatus::Restricted;
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Tier, $this>
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(Tier::class);
    }

    /**
     * @return HasMany<CreatorFeeOverride, $this>
     */
    public function feeOverrides(): HasMany
    {
        return $this->hasMany(CreatorFeeOverride::class);
    }

    /**
     * @return HasMany<PaymentSnapshot, $this>
     */
    public function paymentSnapshots(): HasMany
    {
        return $this->hasMany(PaymentSnapshot::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('visibility', CreatorProfileVisibility::Public);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'handle' => $this->handle,
            'display_name' => $this->display_name,
            'tagline' => $this->tagline,
            'bio' => $this->bio,
            'avatar_url' => $this->avatarUrl(),
            'cover_image_url' => $this->coverImageUrl(),
            'categories' => $this->categories ?? [],
            'website' => $this->website,
            'social_links' => $this->social_links ?? [],
            'support_email' => $this->support_email,
        ];
    }
}
