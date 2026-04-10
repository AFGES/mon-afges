<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class CurrentlyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder
            ->whereTodayOrBefore('started_at')
            ->where(function (Builder $query) {
                $query->whereTodayOrAfter('ended_at')
                    ->orWhereNull('ended_at');
            });
    }
}
