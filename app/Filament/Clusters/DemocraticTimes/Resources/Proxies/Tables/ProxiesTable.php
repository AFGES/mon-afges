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
    /**
     * @param  bool  $showSession  Set to false when the table is already scoped to a single session.
     * @param  bool  $showApprovalStatus  Show the approval status column (for tables listing every approval state).
     * @param  bool  $withRecordActions  Set to false to omit the edit record action and bulk delete.
     */
    public static function configure(Table $table, bool $showSession = true, bool $showApprovalStatus = false, bool $withRecordActions = true): Table
    {
        $table
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
                    ->badge()
                    ->visible($showSession),
                TextColumn::make('approval_status')
                    ->label(__('approval.fields.status'))
                    ->badge()
                    ->sortable()
                    ->visible($showApprovalStatus),
                TextColumn::make('started_at')
                    ->label(__('proxy.fields.started_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label(__('proxy.fields.ended_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
            ->filters([
                //
            ]);

        if ($withRecordActions) {
            $table
                ->recordActions([
                    EditAction::make(),
                ])
                ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->authorizeIndividualRecords('delete'),
                    ]),
                ]);
        }

        return $table;
    }
}
