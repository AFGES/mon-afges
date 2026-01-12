<?php

declare(strict_types=1);

namespace App\Filament\Resources\Proxys;

use App\Filament\Resources\Proxys\Pages\CreateProxy;
use App\Filament\Resources\Proxys\Pages\EditProxy;
use App\Filament\Resources\Proxys\Pages\ListProxys;
use App\Filament\Resources\Proxys\Schemas\ProxyForm;
use App\Filament\Resources\Proxys\Tables\ProxysTable;
use App\Models\Proxy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ProxyResource extends Resource
{
    protected static ?string $model = Proxy::class;

    protected static ?string $slug = 'proxies';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.democratic_moments');
    }

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
        return ProxysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProxys::route('/'),
            'create' => CreateProxy::route('/create'),
            'edit' => EditProxy::route('/{record}/edit'),
        ];
    }
}
