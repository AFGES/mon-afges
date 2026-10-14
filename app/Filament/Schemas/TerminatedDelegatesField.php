<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use App\Models\Delegate;
use App\Services\DelegateApprovalService;
use Closure;
use Filament\Forms\Components\CheckboxList;

final class TerminatedDelegatesField
{
    /**
     * Checkbox list of the current delegates of a seat that may be ended so one more delegate fits in the seat.
     *
     * @param  Closure(): (string|null)  $seatId  Resolves the seat being filled.
     * @param  Closure(): (string|null)|null  $exceptDelegateId  Resolves the delegate to leave out, when it already exists.
     */
    public static function make(Closure $seatId, ?Closure $exceptDelegateId = null): CheckboxList
    {
        $field = CheckboxList::make('terminated_delegate_ids');

        $seat = fn (): ?string => $field->evaluate($seatId);
        $except = fn (): ?string => $exceptDelegateId === null ? null : $field->evaluate($exceptDelegateId);
        $current = fn (): array => self::currentDelegates($seat(), $except());

        return $field
            ->label(__('delegate.approval.terminate_label'))
            ->helperText(fn (): ?string => self::helperText($seat(), $except()))
            ->minItems(fn (): int => self::minimumTerminations($seat(), $except()))
            ->validationMessages([
                'min' => fn (): string => self::capacityExceededMessage(self::minimumTerminations($seat(), $except())),
            ])
            ->options(fn (): array => array_map(
                fn (Delegate $delegate): string => sprintf('%s (%s)', $delegate->user->name, $delegate->started_at?->format('d/m/Y')),
                $current(),
            ))
            ->hidden(fn (): bool => $current() === []);
    }

    /**
     * Keys of the oldest current delegates that must be ended to fit one more delegate in the seat.
     *
     * @return list<string>
     */
    public static function defaultState(?string $seatId, ?string $exceptDelegateId = null): array
    {
        if ($seatId === null) {
            return [];
        }

        $service = app(DelegateApprovalService::class);

        return array_values(
            $service->currentDelegatesOfSeatId($seatId, $exceptDelegateId)
                ->take($service->minimumTerminationsForSeat($seatId, $exceptDelegateId))
                ->modelKeys()
        );
    }

    /**
     * Validation message shown when too few current delegates are ended for the seat capacity.
     */
    public static function capacityExceededMessage(int $minimumTerminations): string
    {
        return trans_choice('delegate.approval.capacity_exceeded', $minimumTerminations, ['min' => $minimumTerminations]);
    }

    /**
     * @return array<string, Delegate>
     */
    private static function currentDelegates(?string $seatId, ?string $exceptDelegateId): array
    {
        if ($seatId === null) {
            return [];
        }

        return app(DelegateApprovalService::class)
            ->currentDelegatesOfSeatId($seatId, $exceptDelegateId)
            ->keyBy('id')
            ->all();
    }

    private static function minimumTerminations(?string $seatId, ?string $exceptDelegateId): int
    {
        if ($seatId === null) {
            return 0;
        }

        return app(DelegateApprovalService::class)->minimumTerminationsForSeat($seatId, $exceptDelegateId);
    }

    private static function helperText(?string $seatId, ?string $exceptDelegateId): ?string
    {
        if ($seatId === null) {
            return null;
        }

        $service = app(DelegateApprovalService::class);

        $occupancy = __('delegate.approval.occupancy', [
            'count' => $service->currentDelegatesOfSeatId($seatId, $exceptDelegateId)->count(),
            'capacity' => $service->seatCapacityForSeat($seatId),
        ]);

        $minimumTerminations = $service->minimumTerminationsForSeat($seatId, $exceptDelegateId);

        if ($minimumTerminations === 0) {
            return $occupancy;
        }

        return $occupancy.' '.trans_choice('delegate.approval.minimum_terminations', $minimumTerminations, ['min' => $minimumTerminations]);
    }
}
