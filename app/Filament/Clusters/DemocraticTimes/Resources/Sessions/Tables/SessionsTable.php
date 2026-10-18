<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Tables;

use App\Enums\SessionStatus;
use App\Enums\SignInStatus;
use App\Models\DemocraticTimeSession;
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
                    ->placeholder(__('democratic_time_session.status.ongoing')),

                TextColumn::make('location')
                    ->label(__('democratic_time_session.fields.location'))
                    ->searchable()
                    ->limit(30),

                TextColumn::make('sign_in_status')
                    ->label(__('emargement.label'))
                    ->badge()
                    ->getStateUsing(fn (DemocraticTimeSession $record): SignInStatus => $record->signInStatus()),

                TextColumn::make('signIns_count')
                    ->label(__('emargement.plural'))
                    ->counts('signIns')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('democratic_time_session.fields.status'))
                    ->badge()
                    ->getStateUsing(fn (DemocraticTimeSession $record): SessionStatus => $record->sessionStatus()),

                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('common.updated_at'))
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
                    ->authorize('update')
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
                    ->authorize('update')
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
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
