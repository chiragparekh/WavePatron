<?php

namespace App\Filament\Resources\CreatorFeeOverrides\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CreatorFeeOverrideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('creator_profile_id')
                    ->relationship('creatorProfile', 'display_name')
                    ->searchable()
                    ->required(),
                TextInput::make('percentage_fee')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                TextInput::make('fixed_fee_cents')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->label('Fixed fee (cents)'),
                TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('usd'),
                DateTimePicker::make('effective_at')
                    ->required()
                    ->default(now()),
            ]);
    }
}
