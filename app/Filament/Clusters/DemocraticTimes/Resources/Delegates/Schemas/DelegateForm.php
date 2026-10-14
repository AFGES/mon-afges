<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Schemas;

use App\Filament\Resources\Organizations\RelationManagers\DelegatesRelationManager as OrganizationsDelegatesRelationManager;
use App\Filament\Resources\Users\RelationManagers\DelegatesRelationManager as UsersDelegatesRelationManager;
use App\Filament\Schemas\Components\ApprovalField;
use App\Models\Seat;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                            ->required(),
                        Select::make('seat_id')
                            ->label(__('delegate.fields.seat'))
                            ->relationship(
                                name: 'seat',
                                modifyQueryUsing: function (Builder $query, Component $livewire): Builder {
                                    $query->with([
                                        'collegeOrganization.college',
                                        'delegates.user',
                                    ]);

                                    $ownerOrganizationId = self::ownerOrganizationId($livewire);

                                    return $ownerOrganizationId === null
                                        ? $query
                                        : $query->whereHas(
                                            'collegeOrganization',
                                            fn (Builder $collegeOrganizationQuery) => $collegeOrganizationQuery->where('organization_id', $ownerOrganizationId),
                                        );
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules([
                                fn (Component $livewire): Closure => function (string $attribute, mixed $value, Closure $fail) use ($livewire): void {
                                    $ownerOrganizationId = self::ownerOrganizationId($livewire);

                                    if ($ownerOrganizationId === null || $value === null) {
                                        return;
                                    }

                                    $seatBelongsToOwner = Seat::query()
                                        ->whereKey($value)
                                        ->whereHas(
                                            'collegeOrganization',
                                            fn (Builder $query) => $query->where('organization_id', $ownerOrganizationId),
                                        )
                                        ->exists();

                                    if (! $seatBelongsToOwner) {
                                        $fail(__('validation.exists', ['attribute' => $attribute]));
                                    }
                                },
                            ])
                            ->helperText(__('delegate.fields.seat_helper')),
                    ]),
                Section::make(__('delegate.sections.approval'))
                    ->schema([
                        ApprovalField::make('approval')
                            ->label(__('delegate.sections.approval'))
                            ->statusAttribute('approval_status')
                            ->approvedAtAttribute('approved_at')
                            ->approvedByAttribute('approved_by')
                            ->rejectionReasonAttribute('rejection_reason')
                            ->approverRelationship('approver')
                            ->hiddenOn('create'),
                    ])
                    ->collapsible()
                    ->hiddenOn('create'),
            ]);
    }

    private static function ownerOrganizationId(Component $livewire): int|string|null
    {
        if (! $livewire instanceof OrganizationsDelegatesRelationManager) {
            return null;
        }

        return $livewire->getOwnerRecord()->getKey();
    }
}
