<?php

namespace App\Filament\Resources\Uploads;

use App\Filament\Resources\Uploads\Pages\EditUpload;
use App\Filament\Resources\Uploads\Pages\ListUploads;
use App\Filament\Resources\Uploads\Schemas\UploadForm;
use App\Filament\Resources\Uploads\Tables\UploadsTable;
use App\Models\Upload;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UploadResource extends Resource
{
    protected static ?string $model = Upload::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static ?string $navigationLabel = 'Uploads';

    protected static ?string $modelLabel = 'upload';

    protected static ?string $pluralModelLabel = 'uploads';

    public static function form(Schema $schema): Schema
    {
        return UploadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UploadsTable::configure($table);
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
            'index' => ListUploads::route('/'),
            'edit' => EditUpload::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
