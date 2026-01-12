<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemocraticTimeTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DemocraticTimeTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('democratic_time_type.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('acronym')
                    ->label(__('democratic_time_type.fields.acronym'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('convocation_interval_days')
                    ->label(__('democratic_time_type.fields.convocation_interval_days'))
                    ->numeric()
                    ->sortable()
                    ->suffix(' jours'),

                TextColumn::make('sessions_count')
                    ->label('Nombre de séances')
                    ->counts('sessions')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
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
