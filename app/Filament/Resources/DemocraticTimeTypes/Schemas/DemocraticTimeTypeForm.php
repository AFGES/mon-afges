<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemocraticTimeTypes\Schemas;

use Filament\Forms\Components\TextInput;
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
            ]);
    }
}
