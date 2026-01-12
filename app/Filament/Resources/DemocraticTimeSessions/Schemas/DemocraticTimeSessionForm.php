<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemocraticTimeSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class DemocraticTimeSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
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
            ]);
    }
}
