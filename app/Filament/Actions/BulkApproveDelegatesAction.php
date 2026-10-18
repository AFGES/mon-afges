<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Delegate;
use App\Services\DelegateApprovalService;
use App\Services\SeatCapacityCalculator;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class BulkApproveDelegatesAction extends BaseBulkApproveAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation(false)
            ->schema([
                Toggle::make('terminate_current_delegates')
                    ->label(__('delegate.approval.bulk_terminate_label'))
                    ->helperText(__('delegate.approval.bulk_terminate_helper'))
                    ->default(false),
            ])
            ->action(function (Collection $selectedRecords, array $data, DelegateApprovalService $service, SeatCapacityCalculator $seatCapacity): void {
                $this->guardRecords($selectedRecords);
                $this->approveDelegates($selectedRecords, $data, auth()->id(), $service, $seatCapacity);
            });
    }

    protected function supports(mixed $record): bool
    {
        return $record instanceof Delegate;
    }

    /**
     * @param  Collection<int, Delegate>  $records
     * @param  array<string, mixed>  $data
     */
    private function approveDelegates(Collection $records, array $data, int|string|null $approvedBy, DelegateApprovalService $service, SeatCapacityCalculator $seatCapacity): void
    {
        $terminate = (bool) ($data['terminate_current_delegates'] ?? false);
        $count = 0;
        $skippedCount = 0;
        $approvedInBatchIds = [];

        /** @var Delegate $record */
        foreach ($records as $record) {
            if (! $record->isPending()) {
                continue;
            }

            if ($terminate) {
                try {
                    $service->approveAndTerminateCurrentDelegates($record, $approvedBy, $approvedInBatchIds);
                } catch (ValidationException) {
                    $skippedCount++;

                    continue;
                }
            } elseif ($seatCapacity->minimumTerminations($record) > 0) {
                $skippedCount++;

                continue;
            } else {
                $service->approve($record, $approvedBy);
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
    }
}
