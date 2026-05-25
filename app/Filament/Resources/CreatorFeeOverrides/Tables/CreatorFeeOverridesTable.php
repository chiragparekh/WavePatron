<?php

namespace App\Filament\Resources\CreatorFeeOverrides\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreatorFeeOverridesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('creatorProfile.display_name')
                    ->label('Creator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('percentage_fee')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('fixed_fee_cents')
                    ->label('Fixed fee')
                    ->sortable(),
                TextColumn::make('currency')
                    ->sortable(),
                TextColumn::make('effective_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('effective_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
