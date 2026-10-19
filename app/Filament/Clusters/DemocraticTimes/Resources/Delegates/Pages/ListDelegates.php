<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Filament\Exports\DelegateExporter;
use App\Filament\Imports\DelegateImporter;
use App\Models\Delegate;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

final class ListDelegates extends ListRecords
{
    protected static string $resource = DelegateResource::class;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(DelegateImporter::class)
                ->label(__('delegate_import.action'))
                ->authorize(fn (): bool => Gate::allows('create', Delegate::class)),
            ExportAction::make()
                ->exporter(DelegateExporter::class)
                ->formats([ExportFormat::Csv])
                ->label(__('delegate_export.action'))
                ->authorize(fn (): bool => Gate::allows('export', Delegate::class)),
            CreateAction::make(),
        ];
    }
}
