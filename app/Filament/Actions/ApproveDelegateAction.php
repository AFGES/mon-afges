<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Schemas\TerminatedDelegatesField;
use App\Models\Contracts\Approvable;
use App\Models\Delegate;
use App\Services\DelegateApprovalService;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

final class ApproveDelegateAction extends BaseApproveAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fillForm(fn (Delegate $record): array => [
            'terminated_delegate_ids' => TerminatedDelegatesField::defaultState($record->seat_id, $record->getKey(), $record->started_at, $record->ended_at),
        ])
            ->schema(fn (Delegate $record): array => [
                Text::make(fn (): HtmlString => self::endedSeatsList($record))
                    ->key('ended_seats')
                    ->visible(fn (): bool => self::endedSeats($record)->isNotEmpty()),
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

    /**
     * @return Collection<int, Delegate>
     */
    private static function endedSeats(Delegate $record): Collection
    {
        return $record->user?->upcomingDelegateSeats($record->started_at ?? now(), $record->getKey()) ?? new Collection;
    }

    private static function endedSeatsList(Delegate $record): HtmlString
    {
        $items = self::endedSeats($record)
            ->loadMissing(['seat.collegeOrganization.college', 'organization'])
            ->map(fn (Delegate $delegate): string => '<li>'.e(sprintf('%s (%s)', $delegate->seat?->label(), $delegate->organization?->short_name)).'</li>')
            ->join('');

        return new HtmlString(e(__('delegate.approval.ended_seats')).'<ul class="list-disc ps-5">'.$items.'</ul>');
    }
}
