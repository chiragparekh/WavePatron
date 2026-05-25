<?php

namespace App\Filament\Resources\Tiers\Schemas;

use App\Enums\TierStatus;
use App\Models\Tier;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('creatorProfile.display_name')
                    ->label('Creator')
                    ->disabled(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (?Tier $record): bool => $record !== null && ! $record->isEditableByCreator()),
                TagsInput::make('benefits')
                    ->disabled(fn (?Tier $record): bool => $record !== null && ! $record->isEditableByCreator()),
                TextInput::make('monthly_price_cents')
                    ->label('Monthly price (cents)')
                    ->numeric()
                    ->required()
                    ->disabled(fn (?Tier $record): bool => $record !== null && ! $record->isEditableByCreator()),
                TextInput::make('annual_price_cents')
                    ->label('Annual price (cents)')
                    ->numeric()
                    ->disabled(fn (?Tier $record): bool => $record !== null && ! $record->isEditableByCreator()),
                Select::make('status')
                    ->options(TierStatus::class)
                    ->disabled(),
                TextInput::make('stripe_product_id')
                    ->disabled(),
                TextInput::make('stripe_monthly_price_id')
                    ->label('Stripe monthly price ID')
                    ->disabled(),
                TextInput::make('stripe_annual_price_id')
                    ->label('Stripe annual price ID')
                    ->disabled(),
            ]);
    }
}
