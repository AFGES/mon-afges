<?php

declare(strict_types=1);

namespace App\Filament\Resources\Delegates;

use App\Filament\Resources\Delegates\Pages\CreateDelegate;
use App\Filament\Resources\Delegates\Pages\EditDelegate;
use App\Filament\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Resources\Delegates\Schemas\DelegateForm;
use App\Filament\Resources\Delegates\Tables\DelegatesTable;
use App\Models\Delegate;
use BackedEnum;
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

    public static function form(Schema $schema): Schema
    {
        return DelegateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DelegatesTable::configure($table);
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
            'index' => ListDelegates::route('/'),
            'create' => CreateDelegate::route('/create'),
            'edit' => EditDelegate::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getModelLabel(): string
    {
        return __('delegate.label');
    }
}
