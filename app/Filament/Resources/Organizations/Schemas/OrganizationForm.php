<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\Schemas;

use App\Models\College;
use Exception;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
                            ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label(__('organization.fields.legal_name'))
                            ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                            ->required()
                            ->maxLength(255),
                        TextInput::make('domain')
                            ->label(__('organization.fields.domain'))
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                            ->maxLength(255)
                            ->placeholder('example.org')
                            ->helperText(__('organization.helpers.domain')),
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
                                    ->prefixIcon(Heroicon::OutlinedAcademicCap)
                                    ->options(College::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (! $state) {
                                            return;
                                        }

                                        $college = self::findCollege($state);

                                        if ($college?->max_seats_per_organization === 1) {
                                            $set('seats', 1);
                                        }

                                        if ($college?->max_delegates_per_seat === 1) {
                                            $set('delegates_per_seat', 1);
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('seats')
                                    ->label(__('college.fields.seats'))
                                    ->prefixIcon(Heroicon::OutlinedHashtag)
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(1)
                                    ->helperText(__('organization.helpers.seats'))
                                    ->hidden(function ($get) {
                                        return self::findCollege($get('college_id'))?->max_seats_per_organization === 1;
                                    })
                                    ->maxValue(function ($get) {
                                        return self::findCollege($get('college_id'))?->max_seats_per_organization;
                                    }),
                                TextInput::make('delegates_per_seat')
                                    ->label(__('college.fields.delegates_per_seat'))
                                    ->prefixIcon(Heroicon::OutlinedHashtag)
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->helperText(__('organization.helpers.delegates_per_seat'))
                                    ->hidden(function ($get) {
                                        return self::findCollege($get('college_id'))?->max_delegates_per_seat === 1;
                                    })
                                    ->maxValue(function ($get) {
                                        return self::findCollege($get('college_id'))?->max_delegates_per_seat;
                                    }),
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

                                $data = $record->colleges->map(function ($college) {
                                    return [
                                        'college_id' => $college->id,
                                        'seats' => $college->pivot->seatRecords()->count(),
                                        'delegates_per_seat' => $college->pivot->delegates_per_seat,
                                    ];
                                })->toArray();

                                $component->state($data);
                            })
                            ->saveRelationshipsUsing(function (Repeater $component, $record, $state) {
                                try {
                                    $collegesData = collect($state)->mapWithKeys(function ($item) {
                                        return [$item['college_id'] => [
                                            'delegates_per_seat' => $item['delegates_per_seat'],
                                            'seats' => $item['seats'],
                                        ]];
                                    })->toArray();

                                    $record->syncCollegesWithSeats($collegesData);

                                    Notification::make()
                                        ->title(__('organization.notifications.seats_updated.title'))
                                        ->body(__('organization.notifications.seats_updated.body'))
                                        ->success()
                                        ->send();
                                } catch (Exception $e) {
                                    Notification::make()
                                        ->title(__('organization.notifications.seats_update_failed.title'))
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();

                                    throw $e;
                                }
                            })
                            ->dehydrated(false),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * Find a college by id, memoized so a single render queries each college once.
     */
    private static function findCollege(int|string|null $collegeId): ?College
    {
        if (! $collegeId) {
            return null;
        }

        return once(fn (): ?College => College::find($collegeId));
    }
}
