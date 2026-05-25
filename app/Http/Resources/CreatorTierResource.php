<?php

namespace App\Http\Resources;

use App\Models\Tier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tier */
class CreatorTierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'benefits' => $this->benefits ?? [],
            'monthly_price' => number_format($this->monthly_price_cents / 100, 2, '.', ''),
            'annual_price' => $this->annual_price_cents === null
                ? null
                : number_format($this->annual_price_cents / 100, 2, '.', ''),
            'status' => $this->status->value,
            'is_editable' => $this->isEditableByCreator(),
            'can_submit' => $request->user()?->can('submit', $this->resource) ?? false,
            'can_activate' => $request->user()?->can('activate', $this->resource) ?? false,
            'can_archive' => $request->user()?->can('archive', $this->resource) ?? false,
            'is_subscribable' => $this->isSubscribable(),
        ];
    }
}
