<?php

namespace App\Filament\Resources\PlatformFeeSettings\Pages;

use App\Filament\Resources\PlatformFeeSettings\PlatformFeeSettingResource;
use App\Support\Activity\LogsFilamentRecordChanges;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformFeeSetting extends EditRecord
{
    use LogsFilamentRecordChanges;

    protected static string $resource = PlatformFeeSettingResource::class;

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
        $this->logFilamentRecordUpdated('fee_setting_updated', [
            'percentage_fee',
            'fixed_fee_cents',
            'currency',
            'effective_at',
        ]);
    }
}
