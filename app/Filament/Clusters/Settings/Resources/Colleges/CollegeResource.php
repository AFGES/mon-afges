<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\Colleges;

use App\Filament\Clusters\Settings\Resources\Colleges\Pages\CreateCollege;
use App\Filament\Clusters\Settings\Resources\Colleges\Pages\EditCollege;
use App\Filament\Clusters\Settings\Resources\Colleges\Pages\ListColleges;
use App\Filament\Clusters\Settings\Resources\Colleges\Schemas\CollegeForm;
use App\Filament\Clusters\Settings\Resources\Colleges\Tables\CollegesTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\College;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class CollegeResource extends Resource
{
    protected static ?string $model = College::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getModelLabel(): string
    {
        return __('college.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('college.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return CollegeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollegesTable::configure($table);
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
            'index' => ListColleges::route('/'),
            'create' => CreateCollege::route('/create'),
            'edit' => EditCollege::route('/{record}/edit'),
        ];
    }
}
