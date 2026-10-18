<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Schemas;

use App\Filament\RelationManagers\BaseDelegatesRelationManager;
use App\Filament\Schemas\Components\ApprovalField;
use App\Filament\Schemas\DelegateFormFields;
use App\Filament\Schemas\TerminatedDelegatesField;
use App\Models\Delegate;
use App\Models\Seat;
use App\Models\User;
use App\Rules\SeatBelongsToOrganization;
use App\Services\SeatCapacityCalculator;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

final class DelegateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('delegate.sections.general'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('started_at')
                                    ->label(__('delegate.started_at'))
                                    ->prefixIcon(Heroicon::OutlinedCalendarDays)
                                    ->default(today())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->live()
                                    ->afterStateUpdated(self::refreshTerminatedDelegates(...))
                                    ->required(),
                                DatePicker::make('ended_at')
                                    ->label(__('delegate.ended_at'))
                                    ->prefixIcon(Heroicon::OutlinedCalendarDays)
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->live()
                                    ->afterStateUpdated(self::refreshTerminatedDelegates(...))
                                    ->after('started_at'),
                            ]),
                        Select::make('user_id')
                            ->label(Str::ucfirst(__('user.label')))
                            ->prefixIcon(Heroicon::OutlinedUser)
                            ->relationship(name: 'user', titleAttribute: 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->hidden(fn (Component $livewire): bool => self::isOwnerField($livewire, 'user_id'))
                            ->required(),
                        TextInput::make('email')
                            ->label(__('delegate.fields.email'))
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('delegate.fields.email_helper')),
                        Select::make('organization_id')
                            ->label(Str::ucfirst(__('organization.label')))
                            ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                            ->relationship(name: 'organization', titleAttribute: 'short_name')
                            ->searchable()
                            ->preload()
                            ->hidden(fn (Component $livewire): bool => self::isOwnerField($livewire, 'organization_id'))
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('seat_id', null))
                            ->required(),
                        Select::make('seat_id')
                            ->label(__('delegate.fields.seat'))
                            ->prefixIcon(Heroicon::OutlinedUserGroup)
                            ->relationship(
                                name: 'seat',
                                modifyQueryUsing: fn (Builder $query, Component $livewire, Get $get): Builder => DelegateFormFields::restrictToOrganization(
                                    $query->with([
                                        'collegeOrganization.college',
                                        'delegates.user',
                                    ]),
                                    self::organizationId($livewire, $get),
                                ),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Seat $record): string => $record->label())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(self::refreshTerminatedDelegates(...))
                            ->required()
                            ->rules([
                                fn (Component $livewire, Get $get): SeatBelongsToOrganization => new SeatBelongsToOrganization(self::organizationId($livewire, $get)),
                                fn (Get $get, ?Model $record, SeatCapacityCalculator $seatCapacity): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record, $seatCapacity): void {
                                    $violation = self::capacityViolationOnEdit($seatCapacity, $record, $value, $get('started_at'), $get('ended_at'));

                                    if ($violation > 0) {
                                        $fail(trans_choice('delegate.approval.capacity_exceeded_on_edit', $violation, ['count' => $violation]));
                                    }
                                },
                            ])
                            ->helperText(__('delegate.fields.seat_helper')),
                        TerminatedDelegatesField::make(
                            seatId: fn (Get $get): ?string => $get('seat_id'),
                            startedAt: fn (Get $get): mixed => $get('started_at'),
                            endedAt: fn (Get $get): mixed => $get('ended_at'),
                        )
                            ->visibleOn('create'),
                    ]),
                Section::make(__('delegate_form.sections.general'))
                    ->relationship('user')
                    ->schema([
                        Grid::make(2)->schema(DelegateFormFields::userComponents()),
                        DelegateFormFields::electedMandatesComponent(),
                    ])
                    ->collapsible()
                    ->hiddenOn('create'),
                Section::make(__('delegate.sections.approval'))
                    ->schema([
                        ApprovalField::make('approval')
                            ->label(__('delegate.sections.approval'))
                            ->statusAttribute('approval_status')
                            ->approvedAtAttribute('approved_at')
                            ->rejectionReasonAttribute('rejection_reason')
                            ->approverRelationship('approver')
                            ->hiddenOn('create'),
                    ])
                    ->collapsible()
                    ->hiddenOn('create'),
            ]);
    }

    /**
     * By how many delegates the seat would exceed its capacity once an approved delegate is moved to it
     * or its dates change. Pending, rejected and unchanged records, and creations, are not checked here.
     */
    private static function capacityViolationOnEdit(SeatCapacityCalculator $seatCapacity, ?Model $record, mixed $seatId, mixed $startedAt, mixed $endedAt): int
    {
        if (! $record instanceof Delegate || ! $record->isApproved() || ! is_string($seatId) || blank($startedAt)) {
            return 0;
        }

        $start = Carbon::parse($startedAt);
        $end = blank($endedAt) ? null : Carbon::parse($endedAt);

        $isUnchanged = $record->seat_id === $seatId
            && $record->started_at?->isSameDay($start)
            && ($record->ended_at === null ? $end === null : $end?->isSameDay($record->ended_at) === true);

        if ($isUnchanged) {
            return 0;
        }

        return $seatCapacity->capacityViolation($seatId, $start, $end, $record->getKey());
    }

    /**
     * Whether the form is rendered inside a delegates relation manager whose owner record fixes the given field.
     */
    private static function isOwnerField(Component $livewire, string $field): bool
    {
        return $livewire instanceof BaseDelegatesRelationManager && $livewire->ownerField() === $field;
    }

    /**
     * The organization whose seats may be picked: the owner record in the organization relation manager,
     * otherwise the organization selected in the form.
     */
    private static function organizationId(Component $livewire, Get $get): int|string|null
    {
        if ($livewire instanceof BaseDelegatesRelationManager && $livewire->ownerField() === 'organization_id') {
            return $livewire->getOwnerRecord()->getKey();
        }

        return $get('organization_id');
    }

    /**
     * Pre-tick the oldest delegates that must give up their mandate for the selected seat and mandate period.
     */
    private static function refreshTerminatedDelegates(Get $get, Set $set, string $operation): void
    {
        if ($operation !== 'create') {
            return;
        }

        $set('terminated_delegate_ids', TerminatedDelegatesField::defaultState(
            $get('seat_id'),
            startedAt: $get('started_at'),
            endedAt: $get('ended_at'),
        ));
    }
}
