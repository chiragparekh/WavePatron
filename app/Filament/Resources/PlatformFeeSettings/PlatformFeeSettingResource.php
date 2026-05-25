<?php

namespace App\Filament\Resources\PlatformFeeSettings;

use App\Filament\Resources\PlatformFeeSettings\Pages\CreatePlatformFeeSetting;
use App\Filament\Resources\PlatformFeeSettings\Pages\EditPlatformFeeSetting;
use App\Filament\Resources\PlatformFeeSettings\Pages\ListPlatformFeeSettings;
use App\Filament\Resources\PlatformFeeSettings\Schemas\PlatformFeeSettingForm;
use App\Filament\Resources\PlatformFeeSettings\Tables\PlatformFeeSettingsTable;
use App\Models\PlatformFeeSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlatformFeeSettingResource extends Resource
{
    protected static ?string $model = PlatformFeeSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Platform fees';

    protected static ?string $modelLabel = 'platform fee setting';

    public static function form(Schema $schema): Schema
    {
        return PlatformFeeSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformFeeSettingsTable::configure($table);
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
            'index' => ListPlatformFeeSettings::route('/'),
            'create' => CreatePlatformFeeSetting::route('/create'),
            'edit' => EditPlatformFeeSetting::route('/{record}/edit'),
        ];
    }
}
