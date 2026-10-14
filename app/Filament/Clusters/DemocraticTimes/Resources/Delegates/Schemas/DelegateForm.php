<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Schemas;

use App\Filament\Resources\Organizations\RelationManagers\DelegatesRelationManager as OrganizationsDelegatesRelationManager;
use App\Filament\Resources\Users\RelationManagers\DelegatesRelationManager as UsersDelegatesRelationManager;
use App\Filament\Schemas\Components\ApprovalField;
use App\Filament\Schemas\DelegateFormFields;
use App\Filament\Schemas\TerminatedDelegatesField;
use App\Models\Seat;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
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
                                    ->default(today())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->required(),
                                DatePicker::make('ended_at')
                                    ->label(__('delegate.ended_at'))
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->after('started_at'),
                            ]),
                        Select::make('user_id')
                            ->label(Str::ucfirst(__('user.label')))
                            ->relationship(name: 'user', titleAttribute: 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->hiddenOn(UsersDelegatesRelationManager::class)
                            ->required(),
                        TextInput::make('email')
                            ->label(__('delegate.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('delegate.fields.email_helper')),
                        Select::make('organization_id')
                            ->label(Str::ucfirst(__('organization.label')))
                            ->relationship(name: 'organization', titleAttribute: 'short_name')
                            ->searchable()
                            ->preload()
                            ->hiddenOn(OrganizationsDelegatesRelationManager::class)
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('seat_id', null))
                            ->required(),
                        Select::make('seat_id')
                            ->label(__('delegate.fields.seat'))
                            ->relationship(
                                name: 'seat',
                                modifyQueryUsing: fn (Builder $query, Component $livewire, Get $get): Builder => $query
                                    ->with([
                                        'collegeOrganization.college',
                                        'delegates.user',
                                    ])
                                    ->whereHas(
                                        'collegeOrganization',
                                        fn (Builder $collegeOrganizationQuery) => $collegeOrganizationQuery->where('organization_id', self::organizationId($livewire, $get)),
                                    ),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set(
                                'terminated_delegate_ids',
                                TerminatedDelegatesField::defaultState($state),
                            ))
                            ->required()
                            ->rules([
                                fn (Component $livewire, Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($livewire, $get): void {
                                    if ($value === null) {
                                        return;
                                    }

                                    $seatBelongsToOrganization = Seat::query()
                                        ->whereKey($value)
                                        ->whereHas(
                                            'collegeOrganization',
                                            fn (Builder $query) => $query->where('organization_id', self::organizationId($livewire, $get)),
                                        )
                                        ->exists();

                                    if (! $seatBelongsToOrganization) {
                                        $fail(__('validation.exists', ['attribute' => $attribute]));
                                    }
                                },
                            ])
                            ->helperText(__('delegate.fields.seat_helper')),
                        TerminatedDelegatesField::make(fn (Get $get): ?string => $get('seat_id'))
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
     * The organization whose seats may be picked: the owner record in the organization relation manager,
     * otherwise the organization selected in the form.
     */
    private static function organizationId(Component $livewire, Get $get): int|string|null
    {
        if ($livewire instanceof OrganizationsDelegatesRelationManager) {
            return $livewire->getOwnerRecord()->getKey();
        }

        return $get('organization_id');
    }
}
