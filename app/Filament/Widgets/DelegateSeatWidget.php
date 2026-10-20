<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\TerminateDelegate;
use App\Filament\Pages\RequestDelegateSeat;
use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

final class DelegateSeatWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static ?int $sort = -1;

    protected string $view = 'filament.widgets.delegate-seat-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->delegateEligibleOrganizations()->exists()
            || $user->hasPendingDelegateSeatRequest()
            || $user->hasCurrentOrUpcomingDelegateSeat()
        );
    }

    public function terminateSeatAction(): Action
    {
        return Action::make('terminateSeat')
            ->label(__('profile.manage_delegates.terminate.action'))
            ->color('danger')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalDescription(__('profile.manage_delegates.terminate.confirm'))
            ->visible(function (array $arguments): bool {
                $delegate = $this->findDelegate($arguments);

                return $delegate !== null && auth()->user()->can('terminate', $delegate);
            })
            ->action(function (array $arguments, TerminateDelegate $terminateDelegate): void {
                if (! $terminateDelegate->handle(auth()->user(), $this->findDelegate($arguments))) {
                    Notification::make()
                        ->title(__('profile.manage_delegates.terminate.not_authorized_title'))
                        ->body(__('profile.manage_delegates.terminate.not_authorized_body'))
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('profile.manage_delegates.terminate.success_title'))
                    ->body(__('profile.manage_delegates.terminate.success_body'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array{pendingCount: int, requestUrl: string, seats: Collection<int, Delegate>, canRequest: bool}
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        $pendingCount = $user->delegates()
            ->withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])
            ->pending()
            ->count();

        return [
            'pendingCount' => $pendingCount,
            'requestUrl' => RequestDelegateSeat::getUrl(),
            'seats' => $user->upcomingDelegateSeats(now())
                ->load(['organization', 'seat.collegeOrganization.college']),
            'canRequest' => $pendingCount === 0 && $user->delegateEligibleOrganizations()->exists(),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function findDelegate(array $arguments): ?Delegate
    {
        $delegateId = $arguments['delegate'] ?? null;

        if (! is_string($delegateId)) {
            return null;
        }

        return Delegate::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])->find($delegateId);
    }
}
