<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Contracts\Approvable;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use LogicException;

/**
 * Shared approval action setup; subclasses decide which models they support and how approval happens.
 */
abstract class BaseApproveAction extends Action
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
            ->visible(fn (Approvable $record): bool => $this->supports($record) && $record->isPending())
            ->action(fn (Approvable $record, array $data) => $this->completeApproval(
                $record,
                fn () => $this->approveRecord($record, $data, auth()->id()),
            ));
    }

    /**
     * Whether this action may approve the given record.
     */
    abstract protected function supports(Approvable $record): bool;

    final public static function getDefaultName(): string
    {
        return 'approve';
    }

    /**
     * Guard the record, run the approval and notify the user.
     *
     * @param  Closure(): mixed  $approve
     */
    final protected function completeApproval(Approvable $record, Closure $approve): void
    {
        $this->guard($record);
        $approve();

        Notification::make()
            ->success()
            ->title(__('approval.notifications.approved.title'))
            ->body(__('approval.notifications.approved.body'))
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function approveRecord(Approvable $record, array $data, int|string|null $approvedBy): void
    {
        $record->approve($approvedBy);
    }

    private function guard(Approvable $record): void
    {
        throw_unless($this->supports($record), LogicException::class, $record::class.' requires a dedicated approval action.');
    }
}
