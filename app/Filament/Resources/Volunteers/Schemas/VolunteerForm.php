<?php

namespace App\Filament\Resources\Volunteers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VolunteerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('position_title')
                    ->required(),
                DateTimePicker::make('started_at')
                    ->required(),
                DateTimePicker::make('ended_at'),
                Select::make('organization_id')
                    ->relationship('organization', 'id')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }
}
