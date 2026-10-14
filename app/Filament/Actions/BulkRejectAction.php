<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

final class BulkRejectAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('approval.actions.reject'))
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->modalHeading(__('approval.modal.bulk_reject.heading'))
            ->modalDescription(__('approval.modal.bulk_reject.description'))
            ->accessSelectedRecords()
            ->deselectRecordsAfterCompletion()
            ->schema([
                Textarea::make('rejection_reason')
                    ->label(__('approval.fields.rejection_reason'))
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (Collection $selectedRecords, array $data): void {
                $count = 0;

                foreach ($selectedRecords as $record) {
                    if ($record->isPending()) {
                        $record->reject($data['rejection_reason']);
                        $count++;
                    }
                }

                Notification::make()
                    ->success()
                    ->title(__('approval.notifications.bulk_rejected.title'))
                    ->body(__('approval.notifications.bulk_rejected.body', ['count' => $count]))
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulkReject';
    }
}
