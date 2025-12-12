<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Schemas;

use App\Enums\VolunteerStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class VolunteerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('volunteer.sections.general'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label(__('volunteer.fields.user')),
                                TextEntry::make('organization.short_name')
                                    ->label(__('volunteer.fields.organization')),
                            ]),
                        TextEntry::make('position_title')
                            ->label(__('volunteer.fields.position_title'))
                            ->placeholder(__('volunteer.placeholders.no_position')),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('started_at')
                                    ->label(__('volunteer.fields.started_at'))
                                    ->date('d/m/Y'),
                                TextEntry::make('ended_at')
                                    ->label(__('volunteer.fields.ended_at'))
                                    ->date('d/m/Y')
                                    ->placeholder(__('volunteer.placeholders.ongoing')),
                            ]),
                    ]),
                Section::make(__('volunteer.sections.approval'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label(__('volunteer.fields.status'))
                                    ->badge()
                                    ->color(fn (VolunteerStatus $state): string => $state->color()),
                                TextEntry::make('approved_at')
                                    ->label(__('volunteer.fields.approved_at'))
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('—'),
                                TextEntry::make('approver.name')
                                    ->label(__('volunteer.fields.approved_by'))
                                    ->placeholder('—'),
                            ]),
                        TextEntry::make('rejection_reason')
                            ->label(__('volunteer.fields.rejection_reason'))
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->status === VolunteerStatus::Rejected),
                    ])
                    ->collapsible(),
                Section::make(__('volunteer.sections.metadata'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('volunteer.fields.created_at'))
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('updated_at')
                                    ->label(__('volunteer.fields.updated_at'))
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('deleted_at')
                                    ->label(__('volunteer.fields.deleted_at'))
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
