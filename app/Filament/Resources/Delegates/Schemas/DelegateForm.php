<?php

declare(strict_types=1);

namespace App\Filament\Resources\Delegates\Schemas;

use App\Enums\ApprovalStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                            ->required(),
                        Select::make('organization_id')
                            ->label(Str::ucfirst(__('organization.label')))
                            ->relationship(name: 'organization', titleAttribute: 'short_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('seat_id')
                            ->label(__('delegate.fields.seat'))
                            ->relationship('seat')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('delegate.fields.seat_helper')),
                    ]),
                Section::make(__('delegate.sections.approval'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label(__('delegate.fields.status'))
                                    ->options(ApprovalStatus::class)
                                    ->default(ApprovalStatus::Pending)
                                    ->native(false)
                                    ->live()
                                    ->required(),
                                DateTimePicker::make('approved_at')
                                    ->label(__('delegate.fields.approved_at'))
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->seconds(false),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('approved_by')
                                    ->label(__('delegate.fields.approved_by'))
                                    ->relationship('approver', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Textarea::make('rejection_reason')
                            ->label(__('delegate.fields.rejection_reason'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->visible(fn ($get) => $get('status') === ApprovalStatus::Rejected->value),
                    ])
                    ->collapsible(),
            ]);
    }
}
