<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Volunteers\Schemas\VolunteerForm;
use App\Filament\Resources\Volunteers\Tables\VolunteersTable;
use App\Models\Scopes\ApprovedScope;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class VolunteersRelationManager extends RelationManager
{
    protected static string $relationship = 'volunteers';

    public static function getTitle($ownerRecord, $livewire): string
    {
        return __('volunteer.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return VolunteerForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return VolunteersTable::configure($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScope(ApprovedScope::class))
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
