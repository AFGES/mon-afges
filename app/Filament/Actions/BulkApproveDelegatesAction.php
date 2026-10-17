<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Delegate;
use App\Services\DelegateApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class BulkApproveDelegatesAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('approval.actions.approve'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->modalHeading(__('approval.modal.bulk_approve.heading'))
            ->modalDescription(__('approval.modal.bulk_approve.description'))
            ->schema([
                Toggle::make('terminate_current_delegates')
                    ->label(__('delegate.approval.bulk_terminate_label'))
                    ->helperText(__('delegate.approval.bulk_terminate_helper'))
                    ->default(false),
            ])
            ->accessSelectedRecords()
            ->authorizeIndividualRecords('update')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $selectedRecords, array $data): void {
                $service = app(DelegateApprovalService::class);
                $terminate = (bool) ($data['terminate_current_delegates'] ?? false);
                $count = 0;
                $skippedCount = 0;
                $approvedInBatchIds = [];

                /** @var Delegate $record */
                foreach ($selectedRecords as $record) {
                    if (! $record->isPending()) {
                        continue;
                    }

                    if ($terminate) {
                        try {
                            $service->approveAndTerminateCurrentDelegates($record, $approvedInBatchIds);
                        } catch (ValidationException) {
                            $skippedCount++;

                            continue;
                        }
                    } elseif ($service->minimumTerminations($record) > 0) {
                        $skippedCount++;

                        continue;
                    } else {
                        $service->approve($record);
                    }

                    $approvedInBatchIds[] = $record->getKey();
                    $count++;
                }

                Notification::make()
                    ->success()
                    ->title(__('approval.notifications.bulk_approved.title'))
                    ->body(__('approval.notifications.bulk_approved.body', ['count' => $count]))
                    ->send();

                if ($skippedCount > 0) {
                    Notification::make()
                        ->warning()
                        ->title(__('delegate.approval.bulk_skipped_title'))
                        ->body(trans_choice('delegate.approval.bulk_skipped_body', $skippedCount, ['count' => $skippedCount]))
                        ->persistent()
                        ->send();
                }
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulkApprove';
    }
}
