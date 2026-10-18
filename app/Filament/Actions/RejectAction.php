<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Contracts\Approvable;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

final class RejectAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('approval.actions.reject'))
            ->tooltip(__('approval.actions.reject_tooltip'))
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->modalHeading(__('approval.modal.reject.heading'))
            ->modalDescription(__('approval.modal.reject.description'))
            ->authorize('update')
            ->visible(fn (Approvable $record): bool => $record->isPending())
            ->schema([
                Textarea::make('rejection_reason')
                    ->label(__('approval.fields.rejection_reason'))
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (Approvable $record, array $data): void {
                $record->reject($data['rejection_reason'], auth()->id());

                Notification::make()
                    ->success()
                    ->title(__('approval.notifications.rejected.title'))
                    ->body(__('approval.notifications.rejected.body'))
                    ->send();
            });
    }

    public static function getDefaultName(): string
    {
        return 'reject';
    }
}
