<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns;

use App\Filament\Clusters\DemocraticTimes\DemocraticTimesCluster;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages\CreateSessionSignIn;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages\EditSessionSignIn;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages\ListSessionSignIns;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Schemas\SessionSignInForm;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Tables\SessionSignInsTable;
use App\Models\SessionSignIn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class SessionSignInResource extends Resource
{
    protected static ?string $model = SessionSignIn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $slug = 'emargements';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = DemocraticTimesCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('emargement.plural');
    }

    public static function getModelLabel(): string
    {
        return __('emargement.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('emargement.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return SessionSignInForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionSignInsTable::configure($table);
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
            'index' => ListSessionSignIns::route('/'),
            'create' => CreateSessionSignIn::route('/create'),
            'edit' => EditSessionSignIn::route('/{record}/edit'),
        ];
    }
}
