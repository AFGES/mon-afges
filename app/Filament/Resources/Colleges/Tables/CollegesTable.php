<?php

namespace App\Filament\Resources\Colleges\Tables;

use App\Models\College;
use App\Models\Delegate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollegesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('college.fields.name'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('can_vote')
                    ->label(__('college.fields.can_vote'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('max_seats_per_organization')
                    ->label(__('college.fields.max_seats_per_organization'))
                    ->badge()
                    ->color('info')
                    ->default('-')
                    ->sortable(),
                TextColumn::make('max_delegates_per_seat')
                    ->label(__('college.fields.max_delegates_per_seat'))
                    ->badge()
                    ->color('success')
                    ->default('-')
                    ->sortable(),
                TextColumn::make('delegates_count')
                    ->label(__('college.fields.delegates_count'))
                    ->state(function (College $record): int {
                        return Delegate::query()
                            ->join('seats', 'delegates.seat_id', '=', 'seats.id')
                            ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
                            ->where('college_organization.college_id', $record->id)
                            ->count();
                    }),
                TextColumn::make('created_at')
                    ->label(__('college.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('college.fields.updated_at'))
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
