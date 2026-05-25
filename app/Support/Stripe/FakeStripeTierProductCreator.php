<?php

namespace App\Support\Stripe;

use App\Contracts\CreatesStripeTierProduct;
use App\Models\Tier;
use Illuminate\Support\Str;

class FakeStripeTierProductCreator implements CreatesStripeTierProduct
{
    /**
     * @return array{product_id: string, price_id_monthly: string, price_id_annual: ?string}
     */
    public function create(Tier $tier): array
    {
        $suffix = Str::lower(Str::random(14));

        return [
            'product_id' => 'prod_'.$suffix,
            'price_id_monthly' => 'price_monthly_'.$suffix,
            'price_id_annual' => $tier->annual_price_cents !== null
                ? 'price_annual_'.$suffix
                : null,
        ];
    }
}
