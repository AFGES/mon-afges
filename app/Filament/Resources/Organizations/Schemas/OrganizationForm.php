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
                Section::make(__('organization.sections.details'))
                    ->schema([
                        TextInput::make('short_name')
                            ->label(__('organization.fields.short_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label(__('organization.fields.legal_name'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('organization.sections.college_associations'))
                    ->description(__('organization.sections.college_associations_description'))
                    ->schema([
                        Repeater::make('collegeAllocations')
                            ->label(__('organization.repeater.label'))
                            ->schema([
                                Select::make('college_id')
                                    ->label(__('college.label'))
                                    ->options(College::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('seats')
                                    ->label(__('college.fields.seats'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(1)
                                    ->helperText(__('organization.helpers.seats')),
                                TextInput::make('delegates_per_seat')
                                    ->label(__('college.fields.delegates_per_seat'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->helperText(__('organization.helpers.delegates_per_seat')),
                            ])
                            ->columns(3)
                            ->addActionLabel(__('organization.repeater.add_action'))
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => College::find($state['college_id'])?->name ?? __('organization.repeater.new_allocation')
                            )
                            ->defaultItems(0)
                            ->afterStateHydrated(function (Repeater $component, $record) {
                                if (! $record) {
                                    return;
                                }

                                $data = $record->colleges->map(fn ($college) => [
                                    'college_id' => $college->id,
                                    'seats' => $college->pivot->seats,
                                    'delegates_per_seat' => $college->pivot->delegates_per_seat,
                                ])->toArray();

                                $component->state($data);
                            })
                            ->saveRelationshipsUsing(function (Repeater $component, $record, $state) {
                                $syncData = collect($state)->mapWithKeys(function ($item) {
                                    return [$item['college_id'] => [
                                        'seats' => $item['seats'],
                                        'delegates_per_seat' => $item['delegates_per_seat'],
                                    ]];
                                })->toArray();

                                $record->colleges()->sync($syncData);
                            })
                            ->dehydrated(false),
                    ])
                    ->collapsible(),
            ]);
    }
}
