<?php

declare(strict_types=1);

namespace App\Filament\Resources\Delegates\Tables;

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
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
                TextColumn::make('organization.short_name')
                    ->label(__('organization.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('seat.collegeOrganization.college.name')
                    ->label(__('College'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('seat.seat_number')
                    ->label(__('Seat #'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('delegate.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (ApprovalStatus $state): string => $state->label())
                    ->color(fn (ApprovalStatus $state): string => $state->color())
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
                SelectFilter::make('status')
                    ->label(__('delegate.filters.status'))
                    ->multiple()
                    ->options([
                        ApprovalStatus::Pending->value => __('approval.status.pending'),
                        ApprovalStatus::Approved->value => __('approval.status.approved'),
                        ApprovalStatus::Rejected->value => __('approval.status.rejected'),
                    ])
                    ->default(function () {
                        $pendingCount = Delegate::withoutGlobalScopes()
                            ->where('status', ApprovalStatus::Pending)
                            ->count();

                        return $pendingCount > 0
                            ? [ApprovalStatus::Pending->value]
                            : [ApprovalStatus::Approved->value];
                    }),
                SelectFilter::make('period')
                    ->label(__('delegate.filters.period'))
                    ->multiple()
                    ->options([
                        'current' => __('delegate.tabs.current'),
                        'old' => __('delegate.tabs.old'),
                        'future' => __('delegate.tabs.future'),
                    ])
                    ->default(['current'])
                    ->query(function (Builder $query, array $data) {
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
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                Action::make('approve')
                    ->label(__('delegate.actions.approve'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->isPending())
                    ->action(function ($record) {
                        $record->approve();
                        Notification::make()
                            ->title(__('delegate.notifications.approved.title'))
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label(__('delegate.actions.reject'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn ($record) => $record->isPending())
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label(__('delegate.fields.rejection_reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject($data['rejection_reason']);
                        Notification::make()
                            ->title(__('delegate.notifications.rejected.title'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultGroup('organization.legal_name');
    }
}
