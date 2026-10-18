<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Schemas\TerminatedDelegatesField;
use App\Models\Contracts\Approvable;
use App\Models\Delegate;
use App\Services\DelegateApprovalService;

final class ApproveDelegateAction extends BaseApproveAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fillForm(fn (Delegate $record): array => [
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
            ->action(fn (Delegate $record, array $data, DelegateApprovalService $service) => $this->completeApproval(
                $record,
                fn () => $service->approve($record, auth()->id(), array_values($data['terminated_delegate_ids'] ?? [])),
            ));
    }

    protected function supports(Approvable $record): bool
    {
        return $record instanceof Delegate;
    }
}
