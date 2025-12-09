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
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state) {
                                            $college = College::find($state);
                                            // Auto-set seats and delegates_per_seat to 1 if max is 1
                                            if ($college && $college->max_seats_per_organization === 1) {
                                                $set('seats', 1);
                                            }
                                            if ($college && $college->max_delegates_per_seat === 1) {
                                                $set('delegates_per_seat', 1);
                                            }
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('seats')
                                    ->label(__('college.fields.seats'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(1)
                                    ->helperText(__('organization.helpers.seats'))
                                    ->live()
                                    ->hidden(function ($get) {
                                        $collegeId = $get('college_id');
                                        if ($collegeId) {
                                            $college = College::find($collegeId);

                                            return $college && $college->max_seats_per_organization === 1;
                                        }

                                        return false;
                                    })
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $collegeId = $get('college_id');
                                        if ($collegeId && $state) {
                                            $college = College::find($collegeId);
                                            if ($college && $college->max_seats_per_organization && $state > $college->max_seats_per_organization) {
                                                $set('seats', $college->max_seats_per_organization);
                                            }
                                        }
                                    })
                                    ->maxValue(function ($get) {
                                        $collegeId = $get('college_id');
                                        if ($collegeId) {
                                            $college = College::find($collegeId);

                                            return $college?->max_seats_per_organization;
                                        }

                                        return null;
                                    }),
                                TextInput::make('delegates_per_seat')
                                    ->label(__('college.fields.delegates_per_seat'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->helperText(__('organization.helpers.delegates_per_seat'))
                                    ->live()
                                    ->hidden(function ($get) {
                                        $collegeId = $get('college_id');
                                        if ($collegeId) {
                                            $college = College::find($collegeId);

                                            return $college && $college->max_delegates_per_seat === 1;
                                        }

                                        return false;
                                    })
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $collegeId = $get('college_id');
                                        if ($collegeId && $state) {
                                            $college = College::find($collegeId);
                                            if ($college && $college->max_delegates_per_seat && $state > $college->max_delegates_per_seat) {
                                                $set('delegates_per_seat', $college->max_delegates_per_seat);
                                            }
                                        }
                                    })
                                    ->maxValue(function ($get) {
                                        $collegeId = $get('college_id');
                                        if ($collegeId) {
                                            $college = College::find($collegeId);

                                            return $college?->max_delegates_per_seat;
                                        }

                                        return null;
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
                                    $collegeOrg = \App\Models\CollegeOrganization::where('college_id', $college->id)
                                        ->where('organization_id', $college->pivot->organization_id)
                                        ->first();

                                    return [
                                        'college_id' => $college->id,
                                        'seats' => $collegeOrg ? $collegeOrg->seatRecords()->count() : 0,
                                        'delegates_per_seat' => $college->pivot->delegates_per_seat,
                                    ];
                                })->toArray();

                                $component->state($data);
                            })
                            ->saveRelationshipsUsing(function (Repeater $component, $record, $state) {
                                $syncData = collect($state)->mapWithKeys(function ($item) {
                                    return [$item['college_id'] => [
                                        'delegates_per_seat' => $item['delegates_per_seat'],
                                    ]];
                                })->toArray();

                                $record->colleges()->sync($syncData);

                                // Now handle seat creation/deletion for each college
                                foreach ($state as $item) {
                                    $collegeOrg = \App\Models\CollegeOrganization::where('college_id', $item['college_id'])
                                        ->where('organization_id', $record->id)
                                        ->first();

                                    if ($collegeOrg) {
                                        $desiredSeats = $item['seats'];
                                        $currentSeats = $collegeOrg->seatRecords()->count();

                                        if ($desiredSeats > $currentSeats) {
                                            // Add more seats
                                            $existingSeatNumbers = $collegeOrg->seatRecords()->pluck('seat_number')->toArray();
                                            $nextSeatNumber = empty($existingSeatNumbers) ? 1 : max($existingSeatNumbers) + 1;

                                            for ($i = 0; $i < ($desiredSeats - $currentSeats); $i++) {
                                                \App\Models\Seat::create([
                                                    'college_organization_id' => $collegeOrg->id,
                                                    'seat_number' => $nextSeatNumber + $i,
                                                ]);
                                            }
                                        } elseif ($desiredSeats < $currentSeats) {
                                            // Remove seats - only empty ones
                                            $seatsToRemove = $currentSeats - $desiredSeats;
                                            $emptySeats = $collegeOrg->seatRecords()
                                                ->whereDoesntHave('delegates')
                                                ->orderBy('seat_number', 'desc')
                                                ->limit($seatsToRemove)
                                                ->get();

                                            if ($emptySeats->count() < $seatsToRemove) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title(__('organization.notifications.cannot_remove_seats.title'))
                                                    ->body(__('organization.notifications.cannot_remove_seats.body', [
                                                        'college' => \App\Models\College::find($item['college_id'])?->name,
                                                        'empty' => $emptySeats->count(),
                                                        'requested' => $seatsToRemove,
                                                    ]))
                                                    ->danger()
                                                    ->send();
                                            } else {
                                                // Soft delete the empty seats
                                                foreach ($emptySeats as $seat) {
                                                    $seat->delete();
                                                }
                                            }
                                        }
                                    }
                                }
                            })
                            ->dehydrated(false),
                    ])
                    ->collapsible(),
            ]);
    }
}
