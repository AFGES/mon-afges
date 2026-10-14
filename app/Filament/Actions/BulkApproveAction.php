<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

final class BulkApproveAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('approval.actions.approve'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->modalHeading(__('approval.modal.bulk_approve.heading'))
            ->modalDescription(__('approval.modal.bulk_approve.description'))
            ->accessSelectedRecords()
            ->authorizeIndividualRecords('update')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $selectedRecords): void {
                $count = 0;

                foreach ($selectedRecords as $record) {
                    if ($record->isPending()) {
                        $record->approve();
                        $count++;
                    }
                }

                Notification::make()
                    ->success()
                    ->title(__('approval.notifications.bulk_approved.title'))
                    ->body(__('approval.notifications.bulk_approved.body', ['count' => $count]))
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulkApprove';
    }
}
