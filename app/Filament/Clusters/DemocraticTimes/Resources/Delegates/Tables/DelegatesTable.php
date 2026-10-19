<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Tables;

use App\Filament\Actions\ApproveDelegateAction;
use App\Filament\Actions\BulkApproveDelegatesAction;
use App\Filament\Actions\BulkRejectAction;
use App\Filament\Actions\RejectAction;
use App\Filament\Exports\DelegateExporter;
use App\Filament\Tables\Filters\ApprovalFilters;
use App\Models\Delegate;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

final class DelegatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('user.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('delegate.fields.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('organization.short_name')
                    ->label(__('organization.label'))
                    ->searchable(),
                TextColumn::make('seat.collegeOrganization.college.name')
                    ->label(__('college.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('approval_status')
                    ->label(__('delegate.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('delegate.started_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label(__('delegate.ended_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label(__('delegate.fields.approved_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approver.name')
                    ->label(__('delegate.fields.approved_by'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                ApprovalFilters::approvalStatus(Delegate::class, __('delegate.filters.status')),
                ApprovalFilters::period(__('delegate.filters.period'), 'delegate.filters'),
            ])
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ApproveDelegateAction::make()->hiddenLabel(),
                    RejectAction::make()->hiddenLabel(),
                ])
                    ->buttonGroup(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkApproveDelegatesAction::make(),
                    BulkRejectAction::make(),
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(DelegateExporter::class)
                        ->formats([ExportFormat::Csv])
                        ->label(__('delegate_export.action'))
                        ->authorize(fn (): bool => Gate::allows('create', Delegate::class)),
                ]),
            ])
            ->defaultGroup('organization.legal_name');
    }
}
