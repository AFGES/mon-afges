<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\SessionResource;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Widgets\SessionQuorumStatusWidget;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Schemas\SessionSignInForm;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageSessionSignIns extends ManageRelatedRecords
{
    protected static string $resource = SessionResource::class;

    protected static string $relationship = 'signIns';

    public static function getNavigationLabel(): string
    {
        return __('emargement.plural');
    }

    public function getTitle(): string
    {
        return __('emargement.plural');
    }

    public function form(Schema $schema): Schema
    {
        return SessionSignInForm::configure($schema);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SessionQuorumStatusWidget::make(['record' => $this->getOwnerRecord()]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with([
                    'seat.collegeOrganization.college',
                    'seat.delegates.user',
                ]))
            ->columns([
                TextColumn::make('seat.id')
                    ->label(__('emargement.fields.seat'))
                    ->formatStateUsing(fn ($record) => $record->seat->label())
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
                    ->relationship(
                        name: 'seat',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn (Builder $query) => $query->with([
                            'collegeOrganization.college',
                            'delegates.user',
                        ])
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
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
