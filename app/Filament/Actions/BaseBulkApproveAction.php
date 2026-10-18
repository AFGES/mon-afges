<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Contracts\Approvable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Shared bulk approval action setup; subclasses decide which models they support and how approval happens.
 */
abstract class BaseBulkApproveAction extends Action
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
            ->action(function (Collection $selectedRecords, array $data): void {
                $this->guardRecords($selectedRecords);
                $this->approveRecords($selectedRecords, $data, auth()->id());
            });
    }

    abstract protected function supports(mixed $record): bool;

    final public static function getDefaultName(): string
    {
        return 'bulkApprove';
    }

    /**
     * @param  Collection<int, *>  $records
     */
    final protected function guardRecords(Collection $records): void
    {
        foreach ($records as $record) {
            throw_unless($this->supports($record), LogicException::class, $record::class.' requires a dedicated approval action.');
        }
    }

    /**
     * @param  Collection<int, Model&Approvable>  $records
     * @param  array<string, mixed>  $data
     */
    protected function approveRecords(Collection $records, array $data, int|string|null $approvedBy): void
    {
        $count = 0;

        foreach ($records as $record) {
            if ($record->isPending()) {
                $record->approve($approvedBy);
                $count++;
            }
        }

        Notification::make()
            ->success()
            ->title(__('approval.notifications.bulk_approved.title'))
            ->body(__('approval.notifications.bulk_approved.body', ['count' => $count]))
            ->send();
    }
}
