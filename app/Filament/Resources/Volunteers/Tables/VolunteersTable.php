<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Tables;

use App\Filament\Actions\ApproveAction;
use App\Filament\Actions\BulkApproveAction;
use App\Filament\Actions\BulkRejectAction;
use App\Filament\Actions\RejectAction;
use App\Filament\Tables\Filters\ApprovalFilters;
use App\Models\Volunteer;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class VolunteersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('volunteer.fields.user'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.short_name')
                    ->label(__('volunteer.fields.organization'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position_title')
                    ->label(__('volunteer.fields.position_title'))
                    ->searchable(),
                TextColumn::make('approval_status')
                    ->label(__('volunteer.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('volunteer.fields.started_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label(__('volunteer.fields.approved_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approver.name')
                    ->label(__('volunteer.fields.approved_by'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ended_at')
                    ->label(__('volunteer.fields.ended_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('volunteer.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('volunteer.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                ApprovalFilters::approvalStatus(Volunteer::class, __('volunteer.filters.status')),
                ApprovalFilters::period(__('volunteer.filters.period'), 'volunteer.filters'),
            ])
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ApproveAction::make()->hiddenLabel(),
                    RejectAction::make()->hiddenLabel(),
                ])
                    ->buttonGroup(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkApproveAction::make(),
                    BulkRejectAction::make(),
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultGroup('organization.legal_name');
    }
}
