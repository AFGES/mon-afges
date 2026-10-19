<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Actions\RegisterPasswordlessUser;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

final class Register extends BaseRegister
{
    private RegisterPasswordlessUser $registerPasswordlessUser;

    public function boot(RegisterPasswordlessUser $registerPasswordlessUser): void
    {
        $this->registerPasswordlessUser = $registerPasswordlessUser;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label(__('user.fields.first_name'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextInput::make('last_name')
                    ->label(__('user.fields.last_name'))
                    ->required()
                    ->maxLength(255),
                $this->getEmailFormComponent(),
            ]);
    }

    /**
     * Send a magic link instead of signing the user in, so only the owner of the address gets access.
     */
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        /** @var array{first_name: string, last_name: string, email: string} $data */
        $data = $this->form->getState();

        try {
            $this->registerPasswordlessUser->handle($data, request()->ip());
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        Notification::make()
            ->title(__('register.magic_link.sent.title'))
            ->body(__('register.magic_link.sent.body'))
            ->success()
            ->send();

        $this->form->fill();

        return null;
    }

    /**
     * The email is not unique: a known address receives a magic link, so accounts cannot be enumerated.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255);
    }
}
