<?php

namespace App\Filament\Resources\CreatorProfiles\Pages;

use App\Filament\Resources\CreatorProfiles\CreatorProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCreatorProfile extends EditRecord
{
    protected static string $resource = CreatorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
