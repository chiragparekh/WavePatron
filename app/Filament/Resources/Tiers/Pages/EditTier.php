<?php

namespace App\Filament\Resources\Tiers\Pages;

use App\Actions\Tier\ApproveTier;
use App\Actions\Tier\ArchiveTier;
use App\Actions\Tier\RejectTier;
use App\Filament\Resources\Tiers\TierResource;
use App\Models\Tier;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTier extends EditRecord
{
    protected static string $resource = TierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Tier $record): bool => auth()->user()?->can('approve', $record) ?? false)
                ->action(function (Tier $record, ApproveTier $approveTier): void {
                    $approveTier->execute($record, auth()->user());

                    Notification::make()
                        ->title('Tier approved')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'stripe_product_id', 'stripe_monthly_price_id', 'stripe_annual_price_id']);
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Tier $record): bool => auth()->user()?->can('reject', $record) ?? false)
                ->action(function (Tier $record, RejectTier $rejectTier): void {
                    $rejectTier->execute($record, auth()->user());

                    Notification::make()
                        ->title('Tier rejected')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            Action::make('archive')
                ->label('Archive')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Tier $record): bool => auth()->user()?->can('archive', $record) ?? false)
                ->action(function (Tier $record, ArchiveTier $archiveTier): void {
                    $archiveTier->execute($record, auth()->user());

                    Notification::make()
                        ->title('Tier archived')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $record;
    }
}
