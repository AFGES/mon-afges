<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers;

use App\Filament\Resources\Volunteers\Pages\CreateVolunteer;
use App\Filament\Resources\Volunteers\Pages\EditVolunteer;
use App\Filament\Resources\Volunteers\Pages\ListVolunteers;
use App\Filament\Resources\Volunteers\Schemas\VolunteerForm;
use App\Filament\Resources\Volunteers\Tables\VolunteersTable;
use App\Models\Scopes\ApprovedScope;
use App\Models\Volunteer;
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

final class VolunteerResource extends Resource
{
    protected static ?string $model = Volunteer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    public static function getModelLabel(): string
    {
        return __('volunteer.label');
    }

    public static function form(Schema $schema): Schema
    {
        return VolunteerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VolunteersTable::configure($table);
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
            'index' => ListVolunteers::route('/'),
            'create' => CreateVolunteer::route('/create'),
            'edit' => EditVolunteer::route('/{record}/edit'),
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
