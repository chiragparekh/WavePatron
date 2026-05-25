<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Spatie\Activitylog\Models\Activity;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event')
                    ->badge(),
                TextEntry::make('log_name')
                    ->label('Log'),
                TextEntry::make('description'),
                TextEntry::make('causer.name')
                    ->label('Causer')
                    ->placeholder('System'),
                TextEntry::make('causer.email')
                    ->label('Causer email')
                    ->placeholder('—'),
                TextEntry::make('subject_type')
                    ->label('Subject type')
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? class_basename($state) : '—'),
                TextEntry::make('subject_id')
                    ->label('Subject ID'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('properties')
                    ->label('Properties')
                    ->formatStateUsing(fn ($state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}')
                    ->columnSpanFull(),
                TextEntry::make('attribute_changes')
                    ->label('Attribute changes')
                    ->formatStateUsing(fn ($state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}')
                    ->visible(fn (Activity $record): bool => filled($record->attribute_changes))
                    ->columnSpanFull(),
            ]);
    }
}
