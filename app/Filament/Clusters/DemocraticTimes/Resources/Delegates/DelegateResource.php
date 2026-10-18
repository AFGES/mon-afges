<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates;

use App\Filament\Clusters\DemocraticTimes\DemocraticTimesCluster;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\CreateDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\EditDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Schemas\DelegateForm;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Tables\DelegatesTable;
use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class DelegateResource extends Resource
{
    protected static ?string $model = Delegate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?string $cluster = DemocraticTimesCluster::class;

    public static function getModelLabel(): string
    {
        return __('delegate.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('delegate.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Delegate::pendingCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return DelegateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DelegatesTable::configure($table);
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
            'index' => ListDelegates::route('/'),
            'create' => CreateDelegate::route('/create'),
            'edit' => EditDelegate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                ApprovedScope::class,
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
                ApprovedScope::class,
            ]);
    }
}
