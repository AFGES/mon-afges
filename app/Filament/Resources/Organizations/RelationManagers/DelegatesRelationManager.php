<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Schemas\DelegateForm;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Tables\DelegatesTable;
use App\Models\Scopes\ApprovedScope;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class DelegatesRelationManager extends RelationManager
{
    protected static string $relationship = 'delegates';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('delegate.relation_managers.organization_title');
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
                CreateAction::make(),
            ]);
    }
}
