<?php

declare(strict_types=1);

namespace App\Filament\Tables\Filters;

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\Proxy;
use App\Models\Volunteer;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class ApprovalFilters
{
    /**
     * Approval status filter, defaulting to pending records when there are any.
     *
     * @param  class-string<Delegate|Proxy|Volunteer>  $modelClass  A model using the HasApprovalStatus trait.
     */
    public static function approvalStatus(string $modelClass, string $label): SelectFilter
    {
        return SelectFilter::make('approval_status')
            ->label($label)
            ->multiple()
            ->options(ApprovalStatus::class)
            ->default(fn (): array => $modelClass::pendingCount() > 0
                ? [ApprovalStatus::Pending]
                : [ApprovalStatus::Approved]);
    }

    /**
     * Current / old / future mandate period filter, using the `{$translationPrefix}.current|old|future` keys.
     */
    public static function period(string $label, string $translationPrefix): SelectFilter
    {
        return SelectFilter::make('period')
            ->label($label)
            ->multiple()
            ->options([
                'current' => __("{$translationPrefix}.current"),
                'old' => __("{$translationPrefix}.old"),
                'future' => __("{$translationPrefix}.future"),
            ])
            ->default(['current'])
            ->query(fn (Builder $query, array $data): Builder => self::applyPeriod($query, $data['value'] ?? null));
    }

    /**
     * @param  Builder<Delegate|Volunteer>  $query
     * @return Builder<Delegate|Volunteer>
     */
    private static function applyPeriod(Builder $query, mixed $period): Builder
    {
        return match ($period) {
            'current' => $query->currentlyActive(),
            'old' => $query->ended(),
            'future' => $query->notStarted(),
            default => $query,
        };
    }
}
