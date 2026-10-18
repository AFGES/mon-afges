<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use App\Models\Delegate;
use App\Services\SeatCapacityCalculator;
use Carbon\CarbonInterface;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class TerminatedDelegatesField
{
    /**
     * Checkbox list of the delegates of a seat that may be ended so one more delegate fits in the seat
     * over the new delegate's mandate period.
     *
     * @param  Closure(): (string|null)  $seatId  Resolves the seat being filled.
     * @param  Closure(): (string|null)|null  $exceptDelegateId  Resolves the delegate to leave out, when it already exists.
     * @param  Closure(): mixed|null  $startedAt  Resolves the start of the new mandate (defaults to today).
     * @param  Closure(): mixed|null  $endedAt  Resolves the end of the new mandate (open-ended when empty).
     */
    public static function make(Closure $seatId, ?Closure $exceptDelegateId = null, ?Closure $startedAt = null, ?Closure $endedAt = null): CheckboxList
    {
        $field = CheckboxList::make('terminated_delegate_ids');

        $seat = fn (): ?string => $field->evaluate($seatId);
        $except = fn (): ?string => $exceptDelegateId === null ? null : $field->evaluate($exceptDelegateId);
        $start = fn (): ?Carbon => $startedAt === null ? null : self::toDate($field->evaluate($startedAt));
        $end = fn (): ?Carbon => $endedAt === null ? null : self::toDate($field->evaluate($endedAt));

        /** @var array<string, array{delegates: Collection<int, Delegate>, capacity: int, minimum: int}> $memo */
        $memo = [];
        $occupancy = function () use ($seat, $except, $start, $end, &$memo): ?array {
            $seatId = $seat();

            if ($seatId === null) {
                return null;
            }

            $exceptId = $except();
            $startDate = $start();
            $endDate = $end();

            return $memo[implode('|', [$seatId, $exceptId, $startDate?->toDateString(), $endDate?->toDateString()])]
                ??= self::resolveOccupancy($seatId, $exceptId, $startDate, $endDate);
        };

        return $field
            ->label(__('delegate.approval.terminate_label'))
            ->helperText(fn (): ?string => self::helperText($occupancy()))
            ->minItems(fn (): int => $occupancy()['minimum'] ?? 0)
            ->validationMessages([
                'min' => fn (): string => self::capacityExceededMessage($occupancy()['minimum'] ?? 0),
            ])
            ->options(fn (): array => ($occupancy()['delegates'] ?? collect())
                ->mapWithKeys(fn (Delegate $delegate): array => [
                    $delegate->getKey() => sprintf('%s (%s)', $delegate->user->name, $delegate->started_at?->format('d/m/Y')),
                ])
                ->all())
            ->hidden(fn (): bool => ($occupancy()['delegates'] ?? collect())->isEmpty());
    }

    /**
     * Keys of the oldest delegates that must be ended to fit one more delegate in the seat over the given period.
     *
     * @return list<string>
     */
    public static function defaultState(?string $seatId, ?string $exceptDelegateId = null, mixed $startedAt = null, mixed $endedAt = null): array
    {
        if ($seatId === null) {
            return [];
        }

        $service = app(SeatCapacityCalculator::class);
        $start = self::toDate($startedAt);
        $end = self::toDate($endedAt);

        return array_values(
            $service->currentDelegatesOfSeatId($seatId, $exceptDelegateId, $start, $end)
                ->take($service->minimumTerminationsForSeat($seatId, $exceptDelegateId, $start, $end))
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
     * @return array{delegates: Collection<int, Delegate>, capacity: int, minimum: int}
     */
    private static function resolveOccupancy(string $seatId, ?string $exceptDelegateId, ?Carbon $startedAt, ?Carbon $endedAt): array
    {
        $service = app(SeatCapacityCalculator::class);

        return [
            'delegates' => $service->currentDelegatesOfSeatId($seatId, $exceptDelegateId, $startedAt, $endedAt),
            'capacity' => $service->seatCapacityForSeat($seatId),
            'minimum' => $service->minimumTerminationsForSeat($seatId, $exceptDelegateId, $startedAt, $endedAt),
        ];
    }

    private static function toDate(mixed $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        return filled($value) ? Carbon::parse($value) : null;
    }

    /**
     * @param  array{delegates: Collection<int, Delegate>, capacity: int, minimum: int}|null  $occupancy
     */
    private static function helperText(?array $occupancy): ?string
    {
        if ($occupancy === null) {
            return null;
        }

        $text = __('delegate.approval.occupancy', [
            'count' => $occupancy['delegates']->count(),
            'capacity' => $occupancy['capacity'],
        ]);

        if ($occupancy['minimum'] === 0) {
            return $text;
        }

        return $text.' '.trans_choice('delegate.approval.minimum_terminations', $occupancy['minimum'], ['min' => $occupancy['minimum']]);
    }
}
