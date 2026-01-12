<?php

declare(strict_types=1);

namespace App\Filament\Resources\SessionSignIns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class SessionSignInForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('emargement.sections.sign_in_details'))
                    ->schema([
                        Select::make('democratic_time_session_id')
                            ->label(__('democratic_time_session.label'))
                            ->relationship('democraticTimeSession', 'location')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        Select::make('seat_id')
                            ->label(__('emargement.fields.seat'))
                            ->relationship('seat')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('signed_in_by_seat_id')
                            ->label(__('emargement.fields.signed_in_by_seat'))
                            ->relationship('signedInBySeat')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label())
                            ->searchable()
                            ->preload()
                            ->helperText(__('emargement.help.signed_in_by_seat', default: 'Laisser vide si le-a délégué-e signe pour lui-elle-même'))
                            ->nullable(),

                        DateTimePicker::make('signed_in_at')
                            ->label(__('emargement.fields.signed_in_at'))
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->default(now()),

                        DateTimePicker::make('signed_out_at')
                            ->label(__('emargement.fields.signed_out_at'))
                            ->seconds(false)
                            ->native(false)
                            ->after('signed_in_at')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
            ]);
    }
}
