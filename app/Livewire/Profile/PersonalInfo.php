<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Filament\Forms\Components\TextInput;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo as BreezyPersonalInfo;

final class PersonalInfo extends BreezyPersonalInfo
{
    /** @var list<string> */
    public array $only = ['first_name', 'last_name', 'email'];

    /**
     * @return list<TextInput>
     */
    protected function getProfileFormComponents(): array
    {
        return [
            TextInput::make('first_name')
                ->label(__('user.fields.first_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label(__('user.fields.last_name'))
                ->required()
                ->maxLength(255),
            $this->getEmailComponent(),
            $this->getCurrentPasswordComponent(),
        ];
    }
}
