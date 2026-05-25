<?php

namespace App\Filament\Resources\Uploads\Schemas;

use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Enums\UploadStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UploadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->disabled(),
                TextInput::make('user.name')
                    ->label('Owner')
                    ->disabled(),
                TextInput::make('original_name')
                    ->disabled(),
                Select::make('status')
                    ->options(UploadStatus::class)
                    ->disabled(),
                TextInput::make('title'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('publish_status')
                    ->options(AudioPublishStatus::class)
                    ->required(),
                Select::make('access_level')
                    ->options(AudioAccessLevel::class)
                    ->required(),
            ]);
    }
}
