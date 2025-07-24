<?php declare(strict_types = 1);

namespace App\Filament\Resources\Delegates\Pages;

use App\Filament\Resources\Delegates\DelegateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListDelegates extends ListRecords
{
    protected static string $resource = DelegateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }


    public function getTabs(): array
    {
        return [
            'active' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereTodayOrBefore('started_at')
                    ->whereTodayOrAfter('ended_at')
                    ->orWhereNull('ended_at')
                )
                ->label(__('delegate.active')),
            'old' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereBeforeToday('ended_at')
                )
                ->label(__('delegate.old')),
            'future' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereAfterToday('started_at')
                )
                ->label(__('delegate.future')),
            'all' => Tab::make()
                ->label(__('delegate.all')),
        ];
    }
}
