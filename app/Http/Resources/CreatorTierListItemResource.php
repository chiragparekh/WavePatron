<?php

namespace App\Http\Resources;

use App\Models\Tier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tier */
class CreatorTierListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'monthly_price' => $this->formattedMonthlyPrice(),
            'annual_price' => $this->formattedAnnualPrice(),
            'is_editable' => $this->isEditableByCreator(),
            'can_submit' => $request->user()?->can('submit', $this->resource) ?? false,
            'can_activate' => $request->user()?->can('activate', $this->resource) ?? false,
            'can_archive' => $request->user()?->can('archive', $this->resource) ?? false,
        ];
    }
}
