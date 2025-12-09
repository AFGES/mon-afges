<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Notifications\MagicLinkNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class Login extends BaseLogin
{
    public bool $showMagicLinkForm = false;

    /**
     * Override the form schema to conditionally show/hide fields.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent()
                    ->visible(fn (): bool => ! $this->showMagicLinkForm),
                $this->getRememberFormComponent()
                    ->visible(fn (): bool => ! $this->showMagicLinkForm),
            ]);
    }

    /**
     * Send magic link.
     */
    public function sendMagicLink(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('email', $data['email'])->first();

        if ($user) {
            // Generate a random token
            $token = Str::random(64);

            // Store hashed token and expiration
            $user->update([
                'magic_link_token' => Hash::make($token),
                'magic_link_token_expires_at' => now()->addMinutes(15),
            ]);

            // Send the notification
            $user->notify(new MagicLinkNotification($token));
        }

        Notification::make()
            ->title('Lien envoyé')
            ->body('Si un compte existe avec cette adresse e-mail, un lien de connexion vous a été envoyé.')
            ->success()
            ->send();
    }

    /**
     * Toggle between password and magic link form.
     */
    public function toggleMagicLinkForm(): void
    {
        $this->showMagicLinkForm = ! $this->showMagicLinkForm;
    }

    /**
     * Authenticate the user or send magic link.
     */
    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        if ($this->showMagicLinkForm) {
            $this->sendMagicLink();

            return null;
        }

        return parent::authenticate();
    }

    /**
     * Add magic link toggle button after the form.
     */
    public function content(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return parent::content($schema)
            ->components([
                ...(parent::content($schema)->getComponents()),
                \Filament\Schemas\Components\Html::make(
                    '<div class="mt-4 text-center">
                        <button
                            type="button"
                            wire:click="toggleMagicLinkForm"
                            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium underline underline-offset-4 outline-none transition duration-75"
                        >
                            '.($this->showMagicLinkForm ? 'Se connecter avec un mot de passe' : 'Se connecter avec un lien magique').'
                        </button>
                    </div>'
                ),
            ]);
    }
}
