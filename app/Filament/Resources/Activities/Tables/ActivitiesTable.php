<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Enums\Role;
use App\Models\CreatorProfile;
use App\Models\PaymentSnapshot;
use App\Models\Tier;
use App\Models\Upload;
use App\Models\User;
use App\Models\WebhookCall;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Causer')
                    ->placeholder('System')
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label('Subject type')
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? class_basename($state) : '—')
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('event')
                        ->distinct()
                        ->orderBy('event')
                        ->pluck('event', 'event')
                        ->all()),
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->all()),
                SelectFilter::make('subject_type')
                    ->label('Subject type')
                    ->options([
                        User::class => 'User',
                        CreatorProfile::class => 'Creator profile',
                        Upload::class => 'Upload',
                        Tier::class => 'Tier',
                        PaymentSnapshot::class => 'Payment snapshot',
                        WebhookCall::class => 'Webhook call',
                    ]),
                TernaryFilter::make('admin_actions')
                    ->label('Admin actions')
                    ->placeholder('All actions')
                    ->trueLabel('Admin only')
                    ->falseLabel('Non-admin only')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHasMorph(
                            'causer',
                            [User::class],
                            fn (Builder $causerQuery): Builder => $causerQuery->role(Role::Admin->value),
                        ),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                            $query
                                ->whereNull('causer_type')
                                ->orWhereHasMorph(
                                    'causer',
                                    [User::class],
                                    fn (Builder $causerQuery): Builder => $causerQuery->whereDoesntHave(
                                        'roles',
                                        fn (Builder $rolesQuery): Builder => $rolesQuery->where('name', Role::Admin->value),
                                    ),
                                );
                        }),
                    ),
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
