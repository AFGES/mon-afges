<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\Delegate;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

final class ManageDelegates extends MyProfileComponent
{
    protected string $view = 'livewire.profile.manage-delegates';

    public static function getSort(): int
    {
        return 110;
    }

    /**
     * @return Collection<int, Delegate>
     */
    public function getDelegatesProperty(): Collection
    {
        return Delegate::withoutGlobalScopes()
            ->where('user_id', auth()->id())
            ->with(['organization', 'seat.collegeOrganization.college'])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    public function terminateDelegate(string $delegateId): void
    {
        $delegate = Delegate::withoutGlobalScopes()
            ->where('id', $delegateId)
            ->firstOrFail();

        if (! auth()->user()->can('terminate', $delegate)) {
            Notification::make()
                ->title(__('profile.manage_delegates.terminate.not_authorized_title'))
                ->body(__('profile.manage_delegates.terminate.not_authorized_body'))
                ->warning()
                ->send();

            return;
        }

        $delegate->end();

        Notification::make()
            ->title(__('profile.manage_delegates.terminate.success_title'))
            ->body(__('profile.manage_delegates.terminate.success_body'))
            ->success()
            ->send();
    }
}
