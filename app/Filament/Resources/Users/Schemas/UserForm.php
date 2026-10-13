<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('user.fields.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('user.fields.email'))
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label(__('user.fields.email_verified_at'))
                    ->disabled(),
                TextInput::make('password')
                    ->label(__('user.fields.password'))
                    ->password()
                    ->disabled(),
                Select::make('roles')
                    ->label(__('user.fields.roles'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }
}
