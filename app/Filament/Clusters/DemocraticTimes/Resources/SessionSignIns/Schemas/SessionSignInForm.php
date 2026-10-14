<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Schemas;

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\ManageSessionSignIns;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SessionSignInForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('emargement.sections.sign_in_details'))
                    ->schema([
                        Select::make('democratic_time_session_id')
                            ->label(__('democratic_time_session.label'))
                            ->relationship('democraticTimeSession', 'location')
                            ->searchable()
                            ->preload()
                            ->hiddenOn(ManageSessionSignIns::class)
                            ->required()
                            ->columnSpan(2),

                        Select::make('seat_id')
                            ->label(__('emargement.fields.seat'))
                            ->relationship(
                                'seat',
                                modifyQueryUsing: fn ($query) => $query->with('collegeOrganization.college')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('delegate_id', null)),

                        Select::make('delegate_id')
                            ->label(__('emargement.fields.delegate'))
                            ->relationship(
                                'delegate',
                                'email',
                                fn ($query, callable $get) => $query
                                    ->when($get('seat_id'), fn ($q, $seatId) => $q->where('seat_id', $seatId))
                                    ->withoutGlobalScopes()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (callable $get) => ! $get('seat_id'))
                            ->helperText(__('emargement.help.delegate')),

                        Select::make('signed_in_by_seat_id')
                            ->label(__('emargement.fields.signed_in_by_seat'))
                            ->relationship(
                                'signedInBySeat',
                                modifyQueryUsing: fn ($query) => $query->with('collegeOrganization.college')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->helperText(__('emargement.help.signed_in_by_seat'))
                            ->nullable(),

                        DateTimePicker::make('signed_in_at')
                            ->label(__('emargement.fields.signed_in_at'))
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->default(now()),

                        DateTimePicker::make('signed_out_at')
                            ->label(__('emargement.fields.signed_out_at'))
                            ->seconds(false)
                            ->native(false)
                            ->after('signed_in_at')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
            ]);
    }
}
