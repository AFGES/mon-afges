<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\Tables;

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
                    ->label('Short Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('legal_name')
                    ->label('Legal Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('colleges_count')
                    ->label('Colleges')
                    ->counts('colleges')
                    ->badge()
                    ->color('info'),
                TextColumn::make('total_seats')
                    ->label('Total Seats')
                    ->state(function ($record): int {
                        return Seat::query()
                            ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
                            ->where('college_organization.organization_id', $record->id)
                            ->count();
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('total_delegates')
                    ->label('Total Delegates')
                    ->state(function ($record): int {
                        return \App\Models\Delegate::query()
                            ->join('seats', 'delegates.seat_id', '=', 'seats.id')
                            ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
                            ->where('college_organization.organization_id', $record->id)
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('college')
                    ->label('College')
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
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
