<?php

namespace App\Filament\Resources\CreatorFeeOverrides;

use App\Filament\Resources\CreatorFeeOverrides\Pages\CreateCreatorFeeOverride;
use App\Filament\Resources\CreatorFeeOverrides\Pages\EditCreatorFeeOverride;
use App\Filament\Resources\CreatorFeeOverrides\Pages\ListCreatorFeeOverrides;
use App\Filament\Resources\CreatorFeeOverrides\Schemas\CreatorFeeOverrideForm;
use App\Filament\Resources\CreatorFeeOverrides\Tables\CreatorFeeOverridesTable;
use App\Models\CreatorFeeOverride;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreatorFeeOverrideResource extends Resource
{
    protected static ?string $model = CreatorFeeOverride::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Creator fee overrides';

    protected static ?string $modelLabel = 'creator fee override';

    public static function form(Schema $schema): Schema
    {
        return CreatorFeeOverrideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreatorFeeOverridesTable::configure($table);
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
            'index' => ListCreatorFeeOverrides::route('/'),
            'create' => CreateCreatorFeeOverride::route('/create'),
            'edit' => EditCreatorFeeOverride::route('/{record}/edit'),
        ];
    }
}
