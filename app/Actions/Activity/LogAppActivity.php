<?php

namespace App\Actions\Activity;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LogAppActivity
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function execute(
        string $event,
        ?Model $subject = null,
        ?User $causer = null,
        array $properties = [],
        ?string $logName = null,
        ?string $description = null,
    ): void {
        $logger = activity($logName)
            ->event($event)
            ->withProperties($properties);

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        if ($causer !== null) {
            $logger->causedBy($causer);
        }

        $logger->log($description ?? $event);
    }
}
