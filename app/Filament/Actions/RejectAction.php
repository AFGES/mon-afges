<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class RejectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reject';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('approval.actions.reject'))
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->modalHeading(__('approval.modal.reject.heading'))
            ->modalDescription(__('approval.modal.reject.description'))
            ->visible(fn ($record): bool => $record->isPending())
            ->form([
                Textarea::make('rejection_reason')
                    ->label(__('approval.fields.rejection_reason'))
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function ($record, array $data): void {
                $record->reject($data['rejection_reason']);

                Notification::make()
                    ->success()
                    ->title(__('approval.notifications.rejected.title'))
                    ->body(__('approval.notifications.rejected.body'))
                    ->send();
            });
    }
}
