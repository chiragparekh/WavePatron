<?php

namespace App\Support\Activity;

use App\Actions\Activity\LogAppActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait LogsFilamentRecordChanges
{
    /** @var array<string, mixed> */
    protected array $originalAttributesBeforeSave = [];

    protected function rememberOriginalAttributesForActivityLog(): void
    {
        $this->originalAttributesBeforeSave = $this->record->getOriginal();
    }

    /**
     * @param  list<string>  $attributes
     */
    protected function logFilamentRecordCreated(string $event, array $attributes, string $logName = 'admin'): void
    {
        $record = $this->record;

        if (! $record instanceof Model) {
            return;
        }

        app(LogAppActivity::class)->execute(
            event: $event,
            subject: $record,
            causer: $this->filamentActivityCauser(),
            properties: [
                'attributes' => $record->only($attributes),
            ],
            logName: $logName,
        );
    }

    /**
     * @param  list<string>  $attributes
     */
    protected function logFilamentRecordUpdated(string $event, array $attributes, string $logName = 'admin'): void
    {
        $record = $this->record;

        if (! $record instanceof Model) {
            return;
        }

        $changes = [];

        foreach ($attributes as $attribute) {
            $from = $this->originalAttributesBeforeSave[$attribute] ?? null;
            $to = $record->getAttribute($attribute);

            if ($from != $to) {
                $changes[$attribute] = [
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        if ($changes === []) {
            return;
        }

        app(LogAppActivity::class)->execute(
            event: $event,
            subject: $record,
            causer: $this->filamentActivityCauser(),
            properties: ['changes' => $changes],
            logName: $logName,
        );
    }

    protected function filamentActivityCauser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
