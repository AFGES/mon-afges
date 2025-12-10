<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Schemas;

use App\Enums\VolunteerStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                                    ->disabled()
                                    ->required(),
                                Select::make('organization_id')
                                    ->label(__('volunteer.fields.organization'))
                                    ->relationship('organization', 'short_name')
                                    ->disabled()
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
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label(__('volunteer.fields.status'))
                                    ->options([
                                        VolunteerStatus::Pending->value => __('volunteer.status.pending'),
                                        VolunteerStatus::Approved->value => __('volunteer.status.approved'),
                                        VolunteerStatus::Rejected->value => __('volunteer.status.rejected'),
                                    ])
                                    ->default(VolunteerStatus::Pending->value)
                                    ->required(),
                                Select::make('approved_by')
                                    ->label(__('volunteer.fields.approved_by'))
                                    ->relationship('approver', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Textarea::make('rejection_reason')
                            ->label(__('volunteer.fields.rejection_reason'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->visible(fn ($get) => $get('status') === VolunteerStatus::Rejected->value),
                    ])
                    ->collapsible(),
            ]);
    }
}
