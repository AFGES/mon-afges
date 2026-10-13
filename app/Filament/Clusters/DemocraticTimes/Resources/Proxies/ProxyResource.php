<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Proxies;

use App\Filament\Clusters\DemocraticTimes\DemocraticTimesCluster;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages\CreateProxy;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages\EditProxy;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages\ListProxies;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Schemas\ProxyForm;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Tables\ProxiesTable;
use App\Models\Proxy;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ProxyResource extends Resource
{
    protected static ?string $model = Proxy::class;

    protected static ?string $slug = 'proxies';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $cluster = DemocraticTimesCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('proxy.plural');
    }

    public static function getModelLabel(): string
    {
        return __('proxy.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('proxy.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        return Proxy::getNavigationBadge();
    }

    public static function form(Schema $schema): Schema
    {
        return ProxyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProxiesTable::configure($table);
    }

    /**
     * @return array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListProxies::route('/'),
            'create' => CreateProxy::route('/create'),
            'edit' => EditProxy::route('/{record}/edit'),
        ];
    }
}
