<?php

namespace App\Filament\Resources\CreatorFeeOverrides\Pages;

use App\Filament\Resources\CreatorFeeOverrides\CreatorFeeOverrideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCreatorFeeOverrides extends ListRecords
{
    protected static string $resource = CreatorFeeOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
