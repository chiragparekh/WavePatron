<?php

namespace App\Filament\Resources\CreatorFeeOverrides\Pages;

use App\Filament\Resources\CreatorFeeOverrides\CreatorFeeOverrideResource;
use App\Support\Activity\LogsFilamentRecordChanges;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCreatorFeeOverride extends EditRecord
{
    use LogsFilamentRecordChanges;

    protected static string $resource = CreatorFeeOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->rememberOriginalAttributesForActivityLog();
    }

    protected function afterSave(): void
    {
        $this->logFilamentRecordUpdated('creator_fee_override_updated', [
            'creator_profile_id',
            'percentage_fee',
            'fixed_fee_cents',
            'currency',
            'effective_at',
        ]);
    }
}
