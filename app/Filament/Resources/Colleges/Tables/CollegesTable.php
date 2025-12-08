<?php

namespace App\Filament\Resources\Colleges\Tables;

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
                TextColumn::make('delegates_count')
                    ->label(__('college.fields.delegates_count'))
                    ->counts('delegates')
                    ->sortable(),
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
