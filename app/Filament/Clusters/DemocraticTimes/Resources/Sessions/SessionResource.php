<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions;

use App\Filament\Clusters\DemocraticTimes\DemocraticTimesCluster;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\CreateSession;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\EditSession;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\ListSessions;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\RelationManagers\ProxiesRelationManager;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\RelationManagers\SessionSignInsRelationManager;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Schemas\SessionForm;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Tables\SessionsTable;
use App\Models\DemocraticTimeSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class SessionResource extends Resource
{
    protected static ?string $model = DemocraticTimeSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $cluster = DemocraticTimesCluster::class;

    public static function getModelLabel(): string
    {
        return __('democratic_time_session.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('democratic_time_session.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return SessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProxiesRelationManager::class,
            SessionSignInsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSessions::route('/'),
            'create' => CreateSession::route('/create'),
            'edit' => EditSession::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['location', 'democraticTimeType.name'];
    }
}
