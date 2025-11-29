<?php declare(strict_types = 1);

namespace App\Filament\Resources\Delegates\Schemas;

use Filament\Forms\Components\{DatePicker, Select};
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class DelegateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('started_at')
                    ->label(__('delegate.started_at'))
                    ->default(today())
                    // ->minDate(today()) TODO: not for admin
                    ->required(),
                DatePicker::make('ended_at')
                    ->label(__('delegate.ended_at'))
                    // ->minDate(today()) TODO: not for admin
                    ->after('started_at'),
                Select::make('user_id')
                    ->label(Str::ucfirst(__('user.label')))
                    ->relationship(name: 'user', titleAttribute: 'name')
                    ->default(auth()->id())
                    ->required(),
                Select::make('organization_id')
                    ->label(Str::ucfirst(__('organization.label')))
                    ->relationship(name: 'organization', titleAttribute: 'short_name')
                    ->required(),
            ]);
    }
}
