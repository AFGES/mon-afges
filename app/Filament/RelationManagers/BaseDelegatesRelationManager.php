<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

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

abstract class BaseDelegatesRelationManager extends RelationManager
{
    protected static string $relationship = 'delegates';

    /**
     * The delegate foreign key fixed by the owner record, hidden from the form (e.g. `user_id`, `organization_id`).
     */
    abstract public function ownerField(): string;

    /**
     * Create an approved delegate, ending the selected current delegates of the seat.
     *
     * @param  array<string, mixed>  $data
     */
    final public static function createApproved(array $data, DelegateApprovalService $service): Model
    {
        return $service->createApproved(
            Arr::except($data, 'terminated_delegate_ids'),
            auth()->id(),
            array_values($data['terminated_delegate_ids'] ?? []),
        );
    }

    final public function form(Schema $schema): Schema
    {
        return DelegateForm::configure($schema);
    }

    final public function table(Table $table): Table
    {
        return DelegatesTable::configure($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScope(ApprovedScope::class))
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data, DelegateApprovalService $service): Model => self::createApproved([
                        ...$data,
                        $this->ownerField() => $this->getOwnerRecord()->getKey(),
                    ], $service)),
            ]);
    }
}
