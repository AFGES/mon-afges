<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes;

use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages\CreateDemocraticTimeType;
use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages\EditDemocraticTimeType;
use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages\ListDemocraticTimeTypes;
use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Schemas\DemocraticTimeTypeForm;
use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Tables\DemocraticTimeTypesTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\DemocraticTimeType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class DemocraticTimeTypeResource extends Resource
{
    protected static ?string $model = DemocraticTimeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getModelLabel(): string
    {
        return __('democratic_time_type.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('democratic_time_type.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return DemocraticTimeTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemocraticTimeTypesTable::configure($table);
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
            'index' => ListDemocraticTimeTypes::route('/'),
            'create' => CreateDemocraticTimeType::route('/create'),
            'edit' => EditDemocraticTimeType::route('/{record}/edit'),
        ];
    }
}
