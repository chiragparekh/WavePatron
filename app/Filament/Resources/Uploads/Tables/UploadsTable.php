<?php

namespace App\Filament\Resources\Uploads\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UploadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Creator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->placeholder(fn ($record) => $record->original_name)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('publish_status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('access_level')
                    ->badge()
                    ->sortable(),
                TextColumn::make('uploaded_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('uploaded_at', 'desc')
            ->filters([
                SelectFilter::make('status'),
                SelectFilter::make('publish_status'),
                SelectFilter::make('access_level'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
