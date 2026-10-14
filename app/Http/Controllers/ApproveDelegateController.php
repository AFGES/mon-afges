<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use App\Services\DelegateApprovalService;
use Illuminate\Http\RedirectResponse;

final class ApproveDelegateController
{
    public function __invoke(Delegate $delegate, DelegateApprovalService $approvalService): RedirectResponse
    {
        if ($approvalService->currentDelegatesOfSeat($delegate)->isNotEmpty()) {
            return redirect()->to(DelegateResource::getUrl('edit', ['record' => $delegate, 'approve' => 1]));
        }

        $delegate->approve();

        return redirect()
            ->to(DelegateResource::getUrl('edit', ['record' => $delegate]))
            ->with('success', __('delegate.notifications.approved.title'));
    }
}
