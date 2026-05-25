<?php

namespace App\Models;

use App\Enums\CreatorPayoutStatus;
use App\Enums\TierStatus;
use App\Policies\TierPolicy;
use Database\Factories\TierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'creator_profile_id',
    'name',
    'benefits',
    'monthly_price_cents',
    'annual_price_cents',
    'status',
    'stripe_product_id',
    'stripe_monthly_price_id',
    'stripe_annual_price_id',
    'requested_at',
    'approved_at',
    'rejected_at',
])]
#[UsePolicy(TierPolicy::class)]
class Tier extends Model
{
    /** @use HasFactory<TierFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'status' => TierStatus::class,
            'monthly_price_cents' => 'integer',
            'annual_price_cents' => 'integer',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function formattedAnnualPrice(): ?string
    {
        if ($this->annual_price_cents === null) {
            return null;
        }

        return '$'.number_format($this->annual_price_cents / 100, 2);
    }

    public function isEditableByCreator(): bool
    {
        return in_array($this->status, [TierStatus::Draft, TierStatus::Rejected], true);
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, [TierStatus::Approved, TierStatus::Active], true);
    }

    public function isPaid(): bool
    {
        return $this->monthly_price_cents > 0;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function isSubscribable(): bool
    {
        return $this->status === TierStatus::Active
            && $this->stripe_monthly_price_id !== null
            && $this->creatorProfile->payoutIsEnabled();
    }

    public function formattedMonthlyPrice(): string
    {
        return '$'.number_format($this->monthly_price_cents / 100, 2);
    }

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TierStatus::Active);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSubscribable(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereNotNull('stripe_monthly_price_id')
            ->whereHas('creatorProfile', fn (Builder $profileQuery) => $profileQuery
                ->where('payout_status', CreatorPayoutStatus::Enabled));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TierStatus::Approved,
            TierStatus::Active,
        ]);
    }
}
