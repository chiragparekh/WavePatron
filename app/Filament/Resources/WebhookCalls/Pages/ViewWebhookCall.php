<?php

namespace App\Filament\Resources\WebhookCalls\Pages;

use App\Filament\Resources\WebhookCalls\WebhookCallResource;
use App\Models\WebhookCall;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Spatie\StripeWebhooks\ProcessStripeWebhookJob;

class ViewWebhookCall extends ViewRecord
{
    protected static string $resource = WebhookCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry')
                ->label('Retry processing')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (WebhookCall $record): bool => $record->exception !== null)
                ->action(function (WebhookCall $record): void {
                    $record->clearException();

                    dispatch(new ProcessStripeWebhookJob($record));

                    Notification::make()
                        ->title('Webhook retry queued')
                        ->success()
                        ->send();
                }),
        ];
    }
}
