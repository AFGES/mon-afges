<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemocraticTimeSessions\RelationManagers;

use App\Filament\Resources\SessionSignIns\Schemas\SessionSignInForm;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class SessionSignInsRelationManager extends RelationManager
{
    protected static string $relationship = 'signIns';

    public static function getTitle($ownerRecord, $livewire): string
    {
        return __('emargement.plural');
    }

    public function form(Schema $schema): Schema
    {
        return SessionSignInForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('seat.label')
                    ->label(__('emargement.fields.seat'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('delegate.email')
                    ->label(__('emargement.fields.delegate'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('signed_in_at')
                    ->label(__('emargement.fields.signed_in_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('signed_out_at')
                    ->label(__('emargement.fields.signed_out_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('emargement.status.open')),

                IconColumn::make('is_signed_in')
                    ->label(__('emargement.fields.is_signed_in'))
                    ->boolean()
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle)
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->isSignedIn()),

                IconColumn::make('is_proxy_sign_in')
                    ->label(__('emargement.fields.is_proxy_sign_in'))
                    ->boolean()
                    ->trueIcon(Heroicon::UserGroup)
                    ->falseIcon(Heroicon::User)
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->isProxySignIn()),
            ])
            ->filters([
                SelectFilter::make('seat_id')
                    ->label(__('emargement.fields.seat'))
                    ->relationship('seat', fn ($record) => $record->label())
                    ->searchable()
                    ->preload(),

                Filter::make('signed_in')
                    ->label(__('emargement.filters.currently_signed_in'))
                    ->query(fn (Builder $query) => $query->whereNull('signed_out_at')),

                Filter::make('signed_out')
                    ->label(__('emargement.filters.signed_out'))
                    ->query(fn (Builder $query) => $query->whereNotNull('signed_out_at')),
            ])
            ->defaultSort('signed_in_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
