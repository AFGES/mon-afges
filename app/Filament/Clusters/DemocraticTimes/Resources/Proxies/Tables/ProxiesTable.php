<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ProxiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'delegatorSeat.collegeOrganization.college',
                'delegatorSeat.delegates.user',
                'proxyHolderSeat.collegeOrganization.college',
                'proxyHolderSeat.delegates.user',
                'democraticTimeSession.democraticTimeType',
            ]))
            ->columns([
                TextColumn::make('delegatorSeat.label')
                    ->label(__('proxy.fields.delegator_seat'))
                    ->getStateUsing(fn ($record) => $record->delegatorSeat->label())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('proxyHolderSeat.label')
                    ->label(__('proxy.fields.proxy_holder_seat'))
                    ->getStateUsing(fn ($record) => $record->proxyHolderSeat->label())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('democraticTimeSession.democraticTimeType.name')
                    ->label(__('proxy.fields.democratic_time_session'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('started_at')
                    ->label(__('proxy.fields.started_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label(__('proxy.fields.ended_at'))
                    ->dateTime('d/m/Y H:i')
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
