<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Actions\SendMagicLink;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

final class Login extends BaseLogin
{
    public bool $showMagicLinkForm = true;

    private SendMagicLink $sendMagicLink;

    public function boot(SendMagicLink $sendMagicLink): void
    {
        $this->sendMagicLink = $sendMagicLink;
    }

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
        $data = $this->form->getState();

        try {
            $this->sendMagicLink->handle($data['email'], request()->ip());
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
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
