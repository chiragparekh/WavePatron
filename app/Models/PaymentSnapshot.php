<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'creator_profile_id',
    'tier_id',
    'subscription_id',
    'gross_amount_cents',
    'stripe_fee_cents',
    'platform_fee_cents',
    'creator_payout_cents',
    'currency',
    'stripe_payment_intent_id',
    'stripe_charge_id',
    'stripe_invoice_id',
    'status',
    'paid_at',
])]
class PaymentSnapshot extends Model
{
    /** @use HasFactory<PaymentSnapshotFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'gross_amount_cents' => 'integer',
            'stripe_fee_cents' => 'integer',
            'platform_fee_cents' => 'integer',
            'creator_payout_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
