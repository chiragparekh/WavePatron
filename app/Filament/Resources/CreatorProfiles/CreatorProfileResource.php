<?php

namespace App\Filament\Resources\CreatorProfiles;

use App\Filament\Resources\CreatorProfiles\Pages\EditCreatorProfile;
use App\Filament\Resources\CreatorProfiles\Pages\ListCreatorProfiles;
use App\Filament\Resources\CreatorProfiles\Schemas\CreatorProfileForm;
use App\Filament\Resources\CreatorProfiles\Tables\CreatorProfilesTable;
use App\Models\CreatorProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreatorProfileResource extends Resource
{
    protected static ?string $model = CreatorProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Creator profiles';

    protected static ?string $modelLabel = 'creator profile';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return CreatorProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreatorProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreatorProfiles::route('/'),
            'edit' => EditCreatorProfile::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
