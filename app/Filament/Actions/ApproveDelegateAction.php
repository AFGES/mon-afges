<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Schemas\TerminatedDelegatesField;
use App\Models\Delegate;
use App\Services\DelegateApprovalService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

final class ApproveDelegateAction extends Action
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
            ->visible(fn (Delegate $record): bool => $record->isPending())
            ->fillForm(fn (Delegate $record): array => [
                'terminated_delegate_ids' => TerminatedDelegatesField::defaultState($record->seat_id, $record->getKey(), $record->started_at, $record->ended_at),
            ])
            ->schema(fn (Delegate $record): array => [
                TerminatedDelegatesField::make(
                    fn (): ?string => $record->seat_id,
                    fn (): ?string => $record->getKey(),
                    fn (): mixed => $record->started_at,
                    fn (): mixed => $record->ended_at,
                ),
            ])
            ->action(function (Delegate $record, array $data): void {
                app(DelegateApprovalService::class)->approve($record, array_values($data['terminated_delegate_ids'] ?? []));

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
