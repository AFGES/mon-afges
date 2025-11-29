<?php declare(strict_types = 1);

namespace App\Filament\Resources\Delegates\Tables;

use App\Models\Delegate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DelegatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('user.label')),
                TextColumn::make('started_at')
                    ->label(__('delegate.started_at'))
                    ->date(),
                TextColumn::make('ended_at')
                    ->label(__('delegate.ended_at'))
                    ->date(),
            ])
            ->filters([
                //TrashedFilter::make(),
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
