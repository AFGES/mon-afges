<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages;

use App\Filament\Actions\ApproveDelegateAction;
use App\Filament\Actions\RejectAction;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

final class EditDelegate extends EditRecord
{
    #[Url]
    public bool $approve = false;

    #[Locked]
    public bool $hasApprovalActionMounted = false;

    protected static string $resource = DelegateResource::class;

    /**
     * Open the approval modal once, when arriving from an approval link for a pending delegate.
     */
    public function booted(): void
    {
        if (! $this->approve || $this->hasApprovalActionMounted) {
            return;
        }

        $this->hasApprovalActionMounted = true;

        /** @var Delegate $delegate */
        $delegate = $this->getRecord();

        if ($delegate->isPending()) {
            $this->cacheTraitActions();
            $this->mountAction('approve');
        }
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            ApproveDelegateAction::make(),
            RejectAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
