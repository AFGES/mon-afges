<?php

namespace App\Filament\Resources\Volunteers\Tables;

use App\Enums\ApprovalStatus;
use App\Filament\Actions\BulkApproveAction;
use App\Filament\Actions\BulkRejectAction;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

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
                    ->options(ApprovalStatus::class)
                    ->default(ApprovalStatus::Pending),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('volunteer.actions.approve'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->isPending())
                    ->action(function ($record) {
                        $record->approve();
                        Notification::make()
                            ->title(__('volunteer.notifications.approved.title'))
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label(__('volunteer.actions.reject'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn ($record) => $record->isPending())
                    ->scheme([
                        Textarea::make('rejection_reason')
                            ->label(__('volunteer.fields.rejection_reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject($data['rejection_reason']);
                        Notification::make()
                            ->title(__('volunteer.notifications.rejected.title'))
                            ->success()
                            ->send();
                    }),
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
