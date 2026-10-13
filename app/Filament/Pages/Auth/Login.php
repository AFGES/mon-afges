<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Notifications\MagicLinkNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class Login extends BaseLogin
{
    public bool $showMagicLinkForm = true;

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
            $token = Str::random(64);

            $user->update([
                'magic_link_token' => Hash::make($token),
                'magic_link_token_expires_at' => now()->addMinutes(15),
            ]);

            $user->notify(new MagicLinkNotification($token));
        }

        Notification::make()
            ->title(__('login.magic_link.sent.title'))
            ->body(__('login.magic_link.sent.body'))
            ->success()
            ->send();
    }

    public function toggleMagicLinkForm(): void
    {
        $this->showMagicLinkForm = ! $this->showMagicLinkForm;
    }

    /**
     * Authenticate the user or send magic link.
     */
    public function authenticate(): ?LoginResponse
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
    public function content(Schema $schema): Schema
    {
        $toggleLabel = $this->showMagicLinkForm
            ? __('login.toggle.password')
            : __('login.toggle.magic_link');

        return parent::content($schema)
            ->components([
                ...(parent::content($schema)->getComponents()),
                View::make('filament.pages.auth.magic-link-toggle')
                    ->viewData(['toggleLabel' => $toggleLabel]),
            ]);
    }
}
