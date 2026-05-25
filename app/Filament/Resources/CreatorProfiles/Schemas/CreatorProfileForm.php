<?php

namespace App\Filament\Resources\CreatorProfiles\Schemas;

use App\Enums\CreatorProfileVisibility;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CreatorProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('handle')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('display_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tagline')
                    ->maxLength(255),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TagsInput::make('categories'),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255),
                KeyValue::make('social_links'),
                TextInput::make('support_email')
                    ->email()
                    ->maxLength(255),
                Select::make('visibility')
                    ->options(CreatorProfileVisibility::class)
                    ->required(),
            ]);
    }
}
