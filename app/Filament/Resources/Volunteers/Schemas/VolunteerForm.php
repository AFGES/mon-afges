<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Schemas;

use App\Filament\Schemas\Components\ApprovalField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class VolunteerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('volunteer.sections.general'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('volunteer.fields.user'))
                                    ->relationship('user', 'name')
                                    ->disabled(fn ($record) => $record !== null)
                                    ->required(),
                                Select::make('organization_id')
                                    ->label(__('volunteer.fields.organization'))
                                    ->relationship('organization', 'short_name')
                                    ->disabled(fn ($record) => $record !== null)
                                    ->required(),
                            ]),
                        TextInput::make('position_title')
                            ->label(__('volunteer.fields.position_title'))
                            ->maxLength(255),
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('started_at')
                                    ->label(__('volunteer.fields.started_at'))
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->required(),
                                DatePicker::make('ended_at')
                                    ->label(__('volunteer.fields.ended_at'))
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->after('started_at'),
                            ]),
                    ]),
                Section::make(__('volunteer.sections.approval'))
                    ->schema([
                        ApprovalField::make('approval')
                            ->label(__('volunteer.sections.approval'))
                            ->statusAttribute('status')
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
