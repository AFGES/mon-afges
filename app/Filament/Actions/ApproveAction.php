<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;

final class ApproveAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('approval.actions.approve'))
            ->tooltip(__('approval.actions.approve_tooltip'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->modalHeading(__('approval.modal.approve.heading'))
            ->modalDescription(__('approval.modal.approve.description'))
            ->authorize('update')
            ->visible(fn ($record): bool => $record->isPending())
            ->action(function ($record): void {
                $record->approve();

                Notification::make()
                    ->success()
                    ->title(__('approval.notifications.approved.title'))
                    ->body(__('approval.notifications.approved.body'))
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'approve';
    }
}
