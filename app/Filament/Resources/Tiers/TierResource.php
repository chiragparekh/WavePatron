<?php

namespace App\Filament\Resources\Tiers;

use App\Filament\Resources\Tiers\Pages\EditTier;
use App\Filament\Resources\Tiers\Pages\ListTiers;
use App\Filament\Resources\Tiers\Schemas\TierForm;
use App\Filament\Resources\Tiers\Tables\TiersTable;
use App\Models\Tier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TierResource extends Resource
{
    protected static ?string $model = Tier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Tier requests';

    protected static ?string $modelLabel = 'tier';

    protected static ?string $pluralModelLabel = 'tiers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TiersTable::configure($table);
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
            'index' => ListTiers::route('/'),
            'edit' => EditTier::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
