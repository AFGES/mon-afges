<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('democraticTimeType.name')
                    ->label(__('democratic_time_session.fields.democratic_time_type'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('started_at')
                    ->label(__('democratic_time_session.fields.started_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('ended_at')
                    ->label(__('democratic_time_session.fields.ended_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('En cours'),

                TextColumn::make('location')
                    ->label(__('democratic_time_session.fields.location'))
                    ->searchable()
                    ->limit(30),

                TextColumn::make('sign_in_status')
                    ->label(__('emargement.label'))
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        if ($record->isSignInOpen()) {
                            return 'open';
                        }
                        if ($record->sign_in_closes_at !== null) {
                            return 'closed';
                        }

                        return 'not_opened';
                    })
                    ->formatStateUsing(fn (string $state): string => __("emargement.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('signIns_count')
                    ->label(__('emargement.plural'))
                    ->counts('signIns')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        if ($record->isCurrentlyActive()) {
                            return 'ongoing';
                        }
                        if ($record->hasEnded()) {
                            return 'ended';
                        }

                        return 'upcoming';
                    })
                    ->formatStateUsing(fn (string $state): string => __("democratic_time_session.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'ongoing' => 'success',
                        'ended' => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('open_sign_in')
                    ->label(__('emargement.actions.open_sign_in'))
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('success')
                    ->visible(fn ($record) => $record->sign_in_opens_at === null)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->openSignIn();
                    })
                    ->successNotificationTitle(__('emargement.messages.sign_in_opened')),

                Action::make('close_sign_in')
                    ->label(__('emargement.actions.close_sign_in'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->visible(fn ($record) => $record->sign_in_opens_at !== null && $record->sign_in_closes_at === null)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->closeSignIn();
                    })
                    ->successNotificationTitle(__('emargement.messages.sign_in_closed')),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
