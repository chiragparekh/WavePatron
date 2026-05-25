<?php

namespace App\Filament\Resources\WebhookCalls\Schemas;

use App\Models\WebhookCall;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WebhookCallInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('payload.type')
                    ->label('Event type'),
                TextEntry::make('payload.id')
                    ->label('Event ID')
                    ->copyable(),
                TextEntry::make('processing_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record): string => $record->processingStatus())
                    ->color(fn (string $state): string => $state === 'failed' ? 'danger' : 'success'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('payload')
                    ->label('Payload')
                    ->formatStateUsing(fn ($state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}')
                    ->columnSpanFull(),
                TextEntry::make('exception_message')
                    ->label('Exception')
                    ->state(fn (WebhookCall $record): string => trim(
                        ($record->exceptionMessage() ?? 'Unknown error')."\n\n".(is_array($record->exception) ? ($record->exception['trace'] ?? '') : '')
                    ))
                    ->visible(fn (WebhookCall $record): bool => $record->exception !== null)
                    ->columnSpanFull(),
            ]);
    }
}
