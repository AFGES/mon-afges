<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('democratic_time_session.label'))
                    ->schema([
                        Select::make('democratic_time_type_id')
                            ->label(__('democratic_time_session.fields.democratic_time_type'))
                            ->relationship('democraticTimeType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        DateTimePicker::make('started_at')
                            ->label(__('democratic_time_session.fields.started_at'))
                            ->required()
                            ->seconds(false)
                            ->native(false),

                        DateTimePicker::make('ended_at')
                            ->label(__('democratic_time_session.fields.ended_at'))
                            ->seconds(false)
                            ->native(false)
                            ->after('started_at'),

                        TextInput::make('location')
                            ->label(__('democratic_time_session.fields.location'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->columnSpan(2),

                Section::make(__('emargement.sections.sign_in_management'))
                    ->schema([
                        Toggle::make('auto_open_sign_in')
                            ->label(__('democratic_time_session.fields.auto_open_sign_in'))
                            ->helperText(__('emargement.help.auto_open'))
                            ->default(true)
                            ->live(),

                        TextInput::make('minutes_before_start')
                            ->label(__('democratic_time_session.fields.minutes_before_start'))
                            ->helperText(__('emargement.help.minutes_before_start'))
                            ->numeric()
                            ->default(15)
                            ->minValue(0)
                            ->maxValue(120)
                            ->required()
                            ->visible(fn ($get) => $get('auto_open_sign_in')),

                        DateTimePicker::make('sign_in_opens_at')
                            ->label(__('democratic_time_session.fields.sign_in_opens_at'))
                            ->helperText(__('emargement.help.sign_in_opens_at'))
                            ->seconds(false)
                            ->native(false)
                            ->disabled(),

                        DateTimePicker::make('sign_in_closes_at')
                            ->label(__('democratic_time_session.fields.sign_in_closes_at'))
                            ->helperText(__('emargement.help.sign_in_closes_at'))
                            ->seconds(false)
                            ->native(false)
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),

                Section::make(__('democratic_time_session.sections.quorum_configuration'))
                    ->description(__('democratic_time_session.help.quorum_preserved'))
                    ->schema([
                        TextInput::make('calculate_quorum_per_college_display')
                            ->label(__('democratic_time_session.fields.calculate_quorum_per_college'))
                            ->default(fn ($record) => $record?->calculate_quorum_per_college
                                ? __('democratic_time_type.fields.calculate_quorum_per_college')
                                : __('emargement.quorum.combined'))
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('quorum_percentage_display')
                            ->label(__('democratic_time_session.fields.quorum_percentage'))
                            ->default(fn ($record) => $record?->quorum_percentage ? $record->quorum_percentage.'%' : '-')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('include_proxies_in_quorum_display')
                            ->label(__('democratic_time_session.fields.include_proxies_in_quorum'))
                            ->default(fn ($record) => $record?->include_proxies_in_quorum ? __('Oui') : __('Non'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->columnSpan(2)
                    ->hidden(fn ($record) => ! $record),
            ]);
    }
}
