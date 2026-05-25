<?php

namespace App\Actions\Tier;

use App\Actions\Activity\LogAppActivity;
use App\Models\Tier;
use App\Models\User;

class LogTierActivity
{
    public function __construct(private LogAppActivity $logAppActivity) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function execute(Tier $tier, string $event, ?User $causer = null, array $properties = []): void
    {
        $this->logAppActivity->execute(
            event: $event,
            subject: $tier,
            causer: $causer,
            properties: $properties,
            logName: 'tier',
        );
    }
}
