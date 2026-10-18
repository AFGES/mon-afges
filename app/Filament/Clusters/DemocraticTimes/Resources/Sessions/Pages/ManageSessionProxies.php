<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Schemas\ProxyForm;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Tables\ProxiesTable;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\SessionResource;
use App\Models\Scopes\ApprovedScope;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ManageSessionProxies extends ManageRelatedRecords
{
    protected static string $resource = SessionResource::class;

    protected static string $relationship = 'proxies';

    public static function getNavigationLabel(): string
    {
        return __('proxy.plural');
    }

    public function getTitle(): string
    {
        return __('proxy.plural');
    }

    public function form(Schema $schema): Schema
    {
        return ProxyForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return ProxiesTable::configure($table, showSession: false, showApprovalStatus: true, withRecordActions: false)
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScope(ApprovedScope::class))
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
