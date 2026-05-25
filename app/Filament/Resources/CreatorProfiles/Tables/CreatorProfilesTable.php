<?php

namespace App\Filament\Resources\CreatorProfiles\Tables;

use App\Enums\CreatorProfileVisibility;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CreatorProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('handle')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('visibility')
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('visibility')
                    ->options(CreatorProfileVisibility::class),
            ])
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
