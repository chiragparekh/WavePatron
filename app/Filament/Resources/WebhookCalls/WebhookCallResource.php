<?php

namespace App\Filament\Resources\WebhookCalls;

use App\Filament\Resources\WebhookCalls\Pages\ListWebhookCalls;
use App\Filament\Resources\WebhookCalls\Pages\ViewWebhookCall;
use App\Filament\Resources\WebhookCalls\Schemas\WebhookCallInfolist;
use App\Filament\Resources\WebhookCalls\Tables\WebhookCallsTable;
use App\Models\WebhookCall;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebhookCallResource extends Resource
{
    protected static ?string $model = WebhookCall::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $navigationLabel = 'Stripe webhooks';

    protected static ?string $modelLabel = 'webhook call';

    protected static ?string $pluralModelLabel = 'webhook calls';

    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Schema $schema): Schema
    {
        return WebhookCallInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookCallsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookCalls::route('/'),
            'view' => ViewWebhookCall::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
