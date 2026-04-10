<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\Colleges\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class CollegeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('college.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Toggle::make('can_vote')
                    ->label(__('college.fields.can_vote'))
                    ->default(true)
                    ->required(),
                TextInput::make('max_seats_per_organization')
                    ->label(__('college.fields.max_seats_per_organization'))
                    ->numeric()
                    ->minValue(0)
                    ->helperText(__('college.helpers.max_seats_per_organization'))
                    ->nullable(),
                TextInput::make('max_delegates_per_seat')
                    ->label(__('college.fields.max_delegates_per_seat'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('college.helpers.max_delegates_per_seat'))
                    ->nullable(),
                Toggle::make('require_same_college_for_proxy')
                    ->label(__('college.fields.require_same_college_for_proxy'))
                    ->helperText(__('college.helpers.require_same_college_for_proxy'))
                    ->default(true)
                    ->required(),
            ]);
    }
}
