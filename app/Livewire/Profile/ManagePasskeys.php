<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

/**
 * Embeds the filament-passkeys manager in the Breezy profile page, whose render hooks
 * only target Filament's own profile page.
 */
final class ManagePasskeys extends MyProfileComponent
{
    protected string $view = 'livewire.profile.manage-passkeys';

    public static function getSort(): int
    {
        return 120;
    }

    /**
     * Mirror the `password.confirm` middleware guarding the passkey registration routes.
     */
    public function getHasRecentPasswordConfirmationProperty(): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        return Date::now()->unix() - $confirmedAt <= Config::integer('auth.password_timeout', 10_800);
    }
}
