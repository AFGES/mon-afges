<?php

namespace App\Filament\Resources\Volunteers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VolunteerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('position_title'),
                TextEntry::make('started_at')
                    ->dateTime(),
                TextEntry::make('ended_at')
                    ->dateTime(),
                TextEntry::make('organization.id')
                    ->numeric(),
                TextEntry::make('user.name')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
