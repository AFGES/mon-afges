<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Schemas;

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\ManageSessionProxies;
use App\Rules\ProxyDelegatesFromSameCollege;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class ProxyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('delegator_seat_id')
                    ->label(__('proxy.fields.delegator_seat'))
                    ->prefixIcon(Heroicon::OutlinedUserGroup)
                    ->helperText(__('proxy.fields.delegator_seat_helper'))
                    ->relationship('delegatorSeat', 'id')
                    ->required()
                    ->live(),
                Select::make('proxy_holder_seat_id')
                    ->label(__('proxy.fields.proxy_holder_seat'))
                    ->prefixIcon(Heroicon::OutlinedUserGroup)
                    ->helperText(__('proxy.fields.proxy_holder_seat_helper'))
                    ->relationship('proxyHolderSeat', 'id')
                    ->required()
                    ->rules([
                        fn (Get $get): ProxyDelegatesFromSameCollege => new ProxyDelegatesFromSameCollege(
                            $get('delegator_seat_id')
                        ),
                    ]),
                Select::make('democratic_time_session_id')
                    ->label(__('proxy.fields.democratic_time_session'))
                    ->prefixIcon(Heroicon::OutlinedPresentationChartBar)
                    ->helperText(__('proxy.fields.democratic_time_session_helper'))
                    ->relationship('democraticTimeSession', 'id')
                    ->hiddenOn(ManageSessionProxies::class)
                    ->required(),
                DateTimePicker::make('started_at')
                    ->label(__('proxy.fields.started_at'))
                    ->prefixIcon(Heroicon::OutlinedClock)
                    ->helperText(__('proxy.fields.started_at_helper')),
                DateTimePicker::make('ended_at')
                    ->label(__('proxy.fields.ended_at'))
                    ->prefixIcon(Heroicon::OutlinedClock)
                    ->helperText(__('proxy.fields.ended_at_helper')),
            ]);
    }
}
