<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait TierValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function tierRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'benefits' => ['required', 'array', 'min:1', 'max:20'],
            'benefits.*' => ['required', 'string', 'max:500'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'annual_price' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }

    /**
     * @return array{name: string, benefits: list<string>, monthly_price_cents: int, annual_price_cents: ?int}
     */
    public function tierAttributes(): array
    {
        $validated = $this->validated();

        $annualPrice = $validated['annual_price'] ?? null;

        return [
            'name' => $validated['name'],
            'benefits' => array_values($validated['benefits']),
            'monthly_price_cents' => (int) round(((float) $validated['monthly_price']) * 100),
            'annual_price_cents' => $annualPrice === null || $annualPrice === ''
                ? null
                : (int) round(((float) $annualPrice) * 100),
        ];
    }
}
