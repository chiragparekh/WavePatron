<?php

namespace App\Listeners;

use App\Actions\Activity\LogAppActivity;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

class LogImpersonationActivity
{
    public function __construct(private LogAppActivity $logAppActivity) {}

    public function handleEnter(EnterImpersonation $event): void
    {
        $this->log('impersonation_started', $event->impersonator, $event->impersonated);
    }

    public function handleLeave(LeaveImpersonation $event): void
    {
        $this->log('impersonation_ended', $event->impersonator, $event->impersonated);
    }

    private function log(string $event, Authenticatable $impersonator, Authenticatable $impersonated): void
    {
        $this->logAppActivity->execute(
            event: $event,
            subject: $impersonated instanceof User ? $impersonated : null,
            causer: $impersonator instanceof User ? $impersonator : null,
            properties: [
                'impersonator' => [
                    'id' => $impersonator->getAuthIdentifier(),
                    'email' => $impersonator->email ?? null,
                ],
                'impersonated' => [
                    'id' => $impersonated->getAuthIdentifier(),
                    'email' => $impersonated->email ?? null,
                ],
            ],
            logName: 'admin',
        );
    }
}
