<?php

namespace App\Models;

use Database\Factories\PlatformFeeSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'percentage_fee',
    'fixed_fee_cents',
    'currency',
    'effective_at',
])]
class PlatformFeeSetting extends Model
{
    /** @use HasFactory<PlatformFeeSettingFactory> */
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

    public static function current(?string $currency = null): ?self
    {
        $currency ??= (string) config('cashier.currency', 'usd');

        return static::query()
            ->where('currency', $currency)
            ->where('effective_at', '<=', now())
            ->orderByDesc('effective_at')
            ->first();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeEffective(Builder $query): Builder
    {
        return $query->where('effective_at', '<=', now());
    }
}
