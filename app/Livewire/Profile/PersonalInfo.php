<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Actions\SubmitDelegateSeatRequest;
use App\Filament\Schemas\DelegateFormFields;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo as BreezyPersonalInfo;
use Livewire\Attributes\Locked;

final class PersonalInfo extends BreezyPersonalInfo
{
    /** @var list<string> */
    public array $only = [...SubmitDelegateSeatRequest::USER_FIELDS, 'email'];

    /**
     * Delegates and pending seat requesters cannot clear the details required by the seat request.
     */
    #[Locked]
    public bool $requirePersonalDetails = false;

    public function mount(): void
    {
        $user = Filament::auth()->user();

        if ($user instanceof User) {
            $user->refresh();
            $this->requirePersonalDetails = $user->hasCurrentOrPendingDelegateSeat();
        }

        parent::mount();
    }

    /**
     * @return list<Field>
     */
    protected function getProfileFormComponents(): array
    {
        return [
            TextInput::make('first_name')
                ->label(__('user.fields.first_name'))
                ->prefixIcon(Heroicon::OutlinedUser)
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label(__('user.fields.last_name'))
                ->prefixIcon(Heroicon::OutlinedUser)
                ->required()
                ->maxLength(255),
            $this->getEmailComponent()->prefixIcon(Heroicon::OutlinedEnvelope),
            ...DelegateFormFields::personalDetailComponents($this->requirePersonalDetails),
            DelegateFormFields::electedMandatesComponent(),
            $this->getCurrentPasswordComponent()->prefixIcon(Heroicon::OutlinedLockClosed),
        ];
    }
}
