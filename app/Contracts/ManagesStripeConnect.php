<?php

namespace App\Contracts;

use App\Models\CreatorProfile;

interface ManagesStripeConnect
{
    public function createAccount(CreatorProfile $profile): string;

    public function createOnboardingLink(CreatorProfile $profile, string $returnUrl, string $refreshUrl): string;

    /**
     * @return array{charges_enabled: bool, details_submitted: bool, payouts_enabled: bool}
     */
    public function fetchAccountCapabilities(string $accountId): array;
}
