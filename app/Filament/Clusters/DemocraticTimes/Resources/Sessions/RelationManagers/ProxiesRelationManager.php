<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\RelationManagers;

use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Schemas\ProxyForm;
use App\Models\Scopes\ApprovedScope;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ProxiesRelationManager extends RelationManager
{
    protected static string $relationship = 'proxies';

    public static function getTitle($ownerRecord, $livewire): string
    {
        return __('proxy.plural');
    }

    public function form(Schema $schema): Schema
    {
        return ProxyForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScope(ApprovedScope::class))
            ->columns([
                TextColumn::make('delegatorSeat.id')
                    ->label(__('proxy.fields.delegator_seat'))
                    ->formatStateUsing(fn ($record) => $record->delegatorSeat->label())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('proxyHolderSeat.id')
                    ->label(__('proxy.fields.proxy_holder_seat'))
                    ->formatStateUsing(fn ($record) => $record->proxyHolderSeat->label())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('approval_status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('proxy.fields.started_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('ended_at')
                    ->label(__('proxy.fields.ended_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
