<?php declare(strict_types = 1);

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('short_name')
                    ->required(),
                TextInput::make('legal_name')
                    ->required(),
            ]);
    }
}
