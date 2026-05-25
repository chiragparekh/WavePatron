<?php

namespace App\Filament\Resources\PlatformFeeSettings\Pages;

use App\Filament\Resources\PlatformFeeSettings\PlatformFeeSettingResource;
use App\Support\Activity\LogsFilamentRecordChanges;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformFeeSetting extends CreateRecord
{
    use LogsFilamentRecordChanges;

    protected static string $resource = PlatformFeeSettingResource::class;

    protected function afterCreate(): void
    {
        $this->logFilamentRecordCreated('fee_setting_created', [
            'percentage_fee',
            'fixed_fee_cents',
            'currency',
            'effective_at',
        ]);
    }
}
