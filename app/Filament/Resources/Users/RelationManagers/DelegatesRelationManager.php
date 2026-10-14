<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Schemas\DelegateForm;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Tables\DelegatesTable;
use App\Models\Scopes\ApprovedScope;
use App\Services\DelegateApprovalService;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class DelegatesRelationManager extends RelationManager
{
    protected static string $relationship = 'delegates';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('delegate.relation_managers.user_title');
    }

    public function form(Schema $schema): Schema
    {
        return DelegateForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return DelegatesTable::configure($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScope(ApprovedScope::class))
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data): Model => app(DelegateApprovalService::class)->createApproved(
                        [...Arr::except($data, 'terminated_delegate_ids'), 'user_id' => $this->getOwnerRecord()->getKey()],
                        array_values($data['terminated_delegate_ids'] ?? []),
                    )),
            ]);
    }
}
