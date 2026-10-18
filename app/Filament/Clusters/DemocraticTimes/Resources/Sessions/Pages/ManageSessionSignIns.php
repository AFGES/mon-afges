<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\SessionResource;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Widgets\SessionQuorumStatusWidget;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Schemas\SessionSignInForm;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Tables\SessionSignInsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Database\Eloquent\Builder;

final class ManageSessionSignIns extends ManageRelatedRecords
{
    protected static string $resource = SessionResource::class;

    protected static string $relationship = 'signIns';

    public static function getNavigationLabel(): string
    {
        return __('emargement.plural');
    }

    public function getTitle(): string
    {
        return __('emargement.plural');
    }

    public function form(Schema $schema): Schema
    {
        return SessionSignInForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return SessionSignInsTable::configure($table, showSession: false, withRecordActions: false)
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with([
                    'seat.collegeOrganization.college',
                    'seat.delegates.user',
                ]))
            ->headerActions([
                CreateAction::make(),
            ]);
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            SessionQuorumStatusWidget::make(['record' => $this->getOwnerRecord()]),
        ];
    }
}
