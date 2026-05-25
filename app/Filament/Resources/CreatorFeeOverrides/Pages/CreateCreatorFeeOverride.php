<?php

namespace App\Filament\Resources\CreatorFeeOverrides\Pages;

use App\Filament\Resources\CreatorFeeOverrides\CreatorFeeOverrideResource;
use App\Support\Activity\LogsFilamentRecordChanges;
use Filament\Resources\Pages\CreateRecord;

class CreateCreatorFeeOverride extends CreateRecord
{
    use LogsFilamentRecordChanges;

    protected static string $resource = CreatorFeeOverrideResource::class;

    protected function afterCreate(): void
    {
        $this->logFilamentRecordCreated('creator_fee_override_created', [
            'creator_profile_id',
            'percentage_fee',
            'fixed_fee_cents',
            'currency',
            'effective_at',
        ]);
    }
}
