<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends \Laravel\Cashier\Subscription
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'local_status' => SubscriptionStatus::class,
        ];
    }

    public function isAccessible(): bool
    {
        if (in_array($this->stripe_status, ['active', 'trialing'], true)) {
            return true;
        }

        return $this->stripe_status === 'canceled'
            && $this->ends_at !== null
            && $this->ends_at->isFuture();
    }

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * @return BelongsTo<Tier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }
}
