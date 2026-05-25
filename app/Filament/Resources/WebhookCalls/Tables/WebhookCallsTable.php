<?php

namespace App\Filament\Resources\WebhookCalls\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WebhookCallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payload.type')
                    ->label('Event type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payload.id')
                    ->label('Event ID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('processing_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record): string => $record->processingStatus())
                    ->color(fn (string $state): string => $state === 'failed' ? 'danger' : 'success'),
                TextColumn::make('exception.message')
                    ->label('Exception')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('processing_status')
                    ->label('Status')
                    ->options([
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'succeeded' => $query->succeeded(),
                            'failed' => $query->failed(),
                            default => $query,
                        };
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From'),
                        DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
