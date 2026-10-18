<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\Tables;

use App\Models\Delegate;
use App\Models\Seat;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('short_name')
                    ->label(__('organization.fields.short_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('legal_name')
                    ->label(__('organization.fields.legal_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('colleges_count')
                    ->label(__('college.plural'))
                    ->counts('colleges')
                    ->badge()
                    ->color('info'),
                TextColumn::make('total_seats')
                    ->label(__('organization.fields.total_seats'))
                    ->state(function ($record): int {
                        return Seat::query()
                            ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
                            ->where('college_organization.organization_id', $record->id)
                            ->count();
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('total_delegates')
                    ->label(__('organization.fields.total_delegates'))
                    ->state(function ($record): int {
                        return Delegate::query()
                            ->join('seats', 'delegates.seat_id', '=', 'seats.id')
                            ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
                            ->where('college_organization.organization_id', $record->id)
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),
                TextColumn::make('created_at')
                    ->label(__('organization.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('organization.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('organization.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('college')
                    ->label(__('college.label'))
                    ->relationship('colleges', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
