<?php

namespace App\Filament\Resources\Proxies\Schemas;

use App\Rules\ProxyDelegatesFromSameCollege;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class ProxyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('delegator_seat_id')
                    ->relationship('delegatorSeat', 'id')
                    ->required()
                    ->live(),
                Select::make('proxy_holder_seat_id')
                    ->relationship('proxyHolderSeat', 'id')
                    ->required()
                    ->rules([
                        fn (Get $get): ProxyDelegatesFromSameCollege => new ProxyDelegatesFromSameCollege(
                            $get('delegator_seat_id')
                        ),
                    ]),
                Select::make('democratic_time_session_id')
                    ->relationship('democraticTimeSession', 'id')
                    ->required(),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('ended_at'),
            ]);
    }
}
