<?php

namespace App\Contracts;

use App\Models\Tier;

interface CreatesStripeTierProduct
{
    /**
     * @return array{product_id: string, price_id_monthly: string, price_id_annual: ?string}
     */
    public function create(Tier $tier): array;
}
