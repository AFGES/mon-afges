<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Schemas;

use App\Filament\Resources\Organizations\RelationManagers\DelegatesRelationManager as OrganizationsDelegatesRelationManager;
use App\Filament\Resources\Users\RelationManagers\DelegatesRelationManager as UsersDelegatesRelationManager;
use App\Filament\Schemas\Components\ApprovalField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
                                modifyQueryUsing: fn ($query) => $query->with([
                                    'collegeOrganization.college',
                                    'delegates.user',
                                ])
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->required()
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
}
