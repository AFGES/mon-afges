<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class BulkApproveAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'bulkApprove';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('approval.actions.approve'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->modalHeading(__('approval.modal.bulk_approve.heading'))
            ->modalDescription(__('approval.modal.bulk_approve.description'))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $count = 0;

                foreach ($records as $record) {
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
}
