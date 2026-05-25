<?php

namespace App\Filament\Resources\PlatformFeeSettings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlatformFeeSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
