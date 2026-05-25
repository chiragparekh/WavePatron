<?php

namespace App\Filament\Resources\Tiers\Tables;

use App\Enums\TierStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('creatorProfile.display_name')
                    ->label('Creator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monthly_price_cents')
                    ->label('Monthly (cents)')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(TierStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
