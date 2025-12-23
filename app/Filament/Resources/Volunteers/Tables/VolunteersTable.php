<?php

namespace App\Filament\Resources\Volunteers\Tables;

use App\Enums\ApprovalStatus;
use App\Filament\Actions\ApproveAction;
use App\Filament\Actions\BulkApproveAction;
use App\Filament\Actions\BulkRejectAction;
use App\Filament\Actions\RejectAction;
use App\Models\Volunteer;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VolunteersTable
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
                TextColumn::make('status')
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
                SelectFilter::make('status')
                    ->label(__('volunteer.filters.status'))
                    ->multiple()
                    ->options(ApprovalStatus::class)
                    ->default(function () {
                        $pendingCount = Volunteer::withoutGlobalScopes()
                            ->where('status', ApprovalStatus::Pending)
                            ->count();

                        return $pendingCount > 0
                            ? [ApprovalStatus::Pending]
                            : [ApprovalStatus::Approved];
                    }),
                SelectFilter::make('period')
                    ->label(__('volunteer.filters.period'))
                    ->multiple()
                    ->options([
                        'current' => __('volunteer.filters.current'),
                        'old' => __('volunteer.filters.old'),
                        'future' => __('volunteer.filters.future'),
                    ])
                    ->default(['current'])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'current' => $query->whereTodayOrBefore('started_at')
                                ->where(function ($q) {
                                    $q->whereTodayOrAfter('ended_at')
                                        ->orWhereNull('ended_at');
                                }),
                            'old' => $query->whereBeforeToday('ended_at'),
                            'future' => $query->whereAfterToday('started_at'),
                            default => $query,
                        };
                    }),
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
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultGroup('organization.legal_name');
    }
}
