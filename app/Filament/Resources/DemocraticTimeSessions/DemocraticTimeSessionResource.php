<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemocraticTimeSessions;

use App\Filament\Resources\DemocraticTimeSessions\Pages\CreateDemocraticTimeSession;
use App\Filament\Resources\DemocraticTimeSessions\Pages\EditDemocraticTimeSession;
use App\Filament\Resources\DemocraticTimeSessions\Pages\ListDemocraticTimeSessions;
use App\Filament\Resources\DemocraticTimeSessions\RelationManagers\ProxiesRelationManager;
use App\Filament\Resources\DemocraticTimeSessions\RelationManagers\SessionSignInsRelationManager;
use App\Filament\Resources\DemocraticTimeSessions\Schemas\DemocraticTimeSessionForm;
use App\Filament\Resources\DemocraticTimeSessions\Tables\DemocraticTimeSessionsTable;
use App\Models\DemocraticTimeSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class DemocraticTimeSessionResource extends Resource
{
    protected static ?string $model = DemocraticTimeSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.democratic_moments');
    }

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
        return DemocraticTimeSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemocraticTimeSessionsTable::configure($table);
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
            'index' => ListDemocraticTimeSessions::route('/'),
            'create' => CreateDemocraticTimeSession::route('/create'),
            'edit' => EditDemocraticTimeSession::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['location', 'democraticTimeType.name'];
    }
}
