<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\Schemas;

use App\Models\College;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Details')
                    ->schema([
                        TextInput::make('short_name')
                            ->label('Short Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Legal Name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('College Associations')
                    ->schema([
                        Repeater::make('colleges')
                            ->relationship('colleges')
                            ->schema([
                                Select::make('id')
                                    ->label('College')
                                    ->options(College::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('seats')
                                    ->label('Number of Seats')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(1)
                                    ->helperText('Total number of seats allocated to this organization in this college'),
                                TextInput::make('delegates_per_seat')
                                    ->label('Delegates per Seat')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->helperText('Number of delegates that can occupy each seat'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add College Association')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => College::find($state['id'])?->name)
                            ->defaultItems(0),
                    ])
                    ->collapsible(),
            ]);
    }
}
