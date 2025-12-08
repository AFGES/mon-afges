<?php

declare(strict_types=1);

namespace App\Filament\Resources\Delegates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class DelegatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('user.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.short_name')
                    ->label(__('organization.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('seat.collegeOrganization.college.name')
                    ->label(__('College'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('seat.seat_number')
                    ->label(__('Seat #'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('delegate.started_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label(__('delegate.ended_at'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                // TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultGroup('organization.legal_name');
    }
}
