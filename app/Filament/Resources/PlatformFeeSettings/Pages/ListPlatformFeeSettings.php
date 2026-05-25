<?php

namespace App\Filament\Resources\PlatformFeeSettings\Pages;

use App\Filament\Resources\PlatformFeeSettings\PlatformFeeSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformFeeSettings extends ListRecords
{
    protected static string $resource = PlatformFeeSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
