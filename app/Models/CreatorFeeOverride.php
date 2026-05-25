<?php

namespace App\Models;

use Database\Factories\CreatorFeeOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'creator_profile_id',
    'percentage_fee',
    'fixed_fee_cents',
    'currency',
    'effective_at',
])]
class CreatorFeeOverride extends Model
{
    /** @use HasFactory<CreatorFeeOverrideFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percentage_fee' => 'decimal:2',
            'fixed_fee_cents' => 'integer',
            'effective_at' => 'datetime',
        ];
    }

    public static function currentFor(CreatorProfile $profile, ?string $currency = null): ?self
    {
        $currency ??= (string) config('cashier.currency', 'usd');

        return static::query()
            ->where('creator_profile_id', $profile->id)
            ->where('currency', $currency)
            ->where('effective_at', '<=', now())
            ->orderByDesc('effective_at')
            ->first();
    }

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }
}
