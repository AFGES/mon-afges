<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemocraticTimeTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DemocraticTimeTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('name')
                    ->label(__('democratic_time_type.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),

                TextInput::make('acronym')
                    ->label(__('democratic_time_type.fields.acronym'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('convocation_interval_days')
                    ->label(__('democratic_time_type.fields.convocation_interval_days'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->suffix('jours')
                    ->columnSpan(3),

                Section::make(__('democratic_time_type.sections.quorum_configuration'))
                    ->schema([
                        Toggle::make('calculate_quorum_per_college')
                            ->label(__('democratic_time_type.fields.calculate_quorum_per_college'))
                            ->default(false)
                            ->helperText(__('democratic_time_type.help.calculate_quorum_per_college')),

                        TextInput::make('quorum_percentage')
                            ->label(__('democratic_time_type.fields.quorum_percentage'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(50)
                            ->helperText(__('democratic_time_type.help.quorum_percentage')),

                        Toggle::make('include_proxies_in_quorum')
                            ->label(__('democratic_time_type.fields.include_proxies_in_quorum'))
                            ->default(true)
                            ->helperText(__('democratic_time_type.help.include_proxies_in_quorum')),
                    ])
                    ->columns(3)
                    ->columnSpan(3),
            ]);
    }
}
