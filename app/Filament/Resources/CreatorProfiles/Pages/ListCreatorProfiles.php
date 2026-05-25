<?php

namespace App\Filament\Resources\CreatorProfiles\Pages;

use App\Filament\Resources\CreatorProfiles\CreatorProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCreatorProfiles extends ListRecords
{
    protected static string $resource = CreatorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
