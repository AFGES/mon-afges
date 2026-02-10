<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class SessionSignInsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('democraticTimeSession.location')
                    ->label(__('democratic_time_session.label'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('seat.label')
                    ->label(__('emargement.fields.seat'))
                    ->getStateUsing(fn ($record) => $record->seat->label())
                    ->searchable()
                    ->sortable(),

                TextColumn::make('delegate.email')
                    ->label(__('emargement.fields.delegate'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('signed_in_at')
                    ->label(__('emargement.fields.signed_in_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('signed_out_at')
                    ->label(__('emargement.fields.signed_out_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('is_signed_in')
                    ->label(__('emargement.fields.is_signed_in'))
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isSignedIn())
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle)
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('is_proxy_sign_in')
                    ->label(__('emargement.fields.is_proxy_sign_in'))
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isProxySignIn())
                    ->trueIcon(Heroicon::CheckBadge)
                    ->falseIcon(Heroicon::OutlinedMinusCircle)
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('signedInBySeat.label')
                    ->label(__('emargement.fields.signed_in_by_seat'))
                    ->getStateUsing(fn ($record) => $record->signedInBySeat?->label())
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('democratic_time_session_id')
                    ->label(__('democratic_time_session.label'))
                    ->relationship('democraticTimeSession', 'location')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('is_signed_in')
                    ->label(__('emargement.fields.is_signed_in'))
                    ->options([
                        'yes' => __('Présent-e'),
                        'no' => __('Sorti-e'),
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'yes') {
                            return $query->whereNull('signed_out_at');
                        }
                        if ($state['value'] === 'no') {
                            return $query->whereNotNull('signed_out_at');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('signed_in_at', 'desc');
    }
}
