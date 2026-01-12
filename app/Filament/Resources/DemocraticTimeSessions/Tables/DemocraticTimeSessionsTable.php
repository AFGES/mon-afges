<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemocraticTimeSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DemocraticTimeSessionsTable
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

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->isCurrentlyActive()) {
                            return 'En cours';
                        }
                        if ($record->hasEnded()) {
                            return 'Terminée';
                        }

                        return 'À venir';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'En cours' => Color::Green,
                        'Terminée' => Color::Gray,
                        'À venir' => Color::Blue,
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
