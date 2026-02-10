<?php

declare(strict_types=1);

namespace App\Filament\Resources\Delegates\Tables;

use App\Enums\ApprovalStatus;
use App\Filament\Actions\ApproveAction;
use App\Filament\Actions\BulkApproveAction;
use App\Filament\Actions\BulkRejectAction;
use App\Filament\Actions\RejectAction;
use App\Models\Delegate;
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
                    ->searchable()
                    ->sortable(),
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
                    ->date()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label(__('delegate.ended_at'))
                    ->date()
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
                SelectFilter::make('approval_status')
                    ->label(__('delegate.filters.status'))
                    ->multiple()
                    ->options(ApprovalStatus::class)
                    ->default(function () {
                        $pendingCount = Delegate::withoutGlobalScopes()
                            ->where('approval_status', ApprovalStatus::Pending)
                            ->count();

                        return $pendingCount > 0
                            ? [ApprovalStatus::Pending]
                            : [ApprovalStatus::Approved];
                    }),
                SelectFilter::make('period')
                    ->label(__('delegate.filters.period'))
                    ->multiple()
                    ->options([
                        'current' => __('delegate.filters.current'),
                        'old' => __('delegate.filters.old'),
                        'future' => __('delegate.filters.future'),
                    ])
                    ->default(['current'])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'current' => $query->currentlyActive(),
                            'old' => $query->ended(),
                            'future' => $query->notStarted(),
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
            ->defaultGroup('organization.legal_name');
    }
}
