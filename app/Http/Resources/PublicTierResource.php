<?php

namespace App\Http\Resources;

use App\Models\Tier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tier */
class PublicTierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'benefits' => $this->benefits ?? [],
            'monthly_price' => number_format($this->monthly_price_cents / 100, 2, '.', ''),
            'annual_price' => $this->annual_price_cents === null
                ? null
                : number_format($this->annual_price_cents / 100, 2, '.', ''),
            'is_subscribable' => $this->isSubscribable(),
            'subscribe_url' => $this->isSubscribable()
                ? route('creators.subscribe', [
                    'profile' => $this->creatorProfile->handle,
                    'tier' => $this->id,
                ])
                : null,
        ];
    }
}
