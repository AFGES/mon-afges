<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use App\Services\DelegateApprovalService;
use App\Services\SeatCapacityCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class ApproveDelegateController
{
    public function __invoke(Delegate $delegate, DelegateApprovalService $approvalService, SeatCapacityCalculator $seatCapacity): RedirectResponse
    {
        if ($seatCapacity->currentDelegatesOfSeat($delegate)->isNotEmpty()) {
            return redirect()->to(DelegateResource::getUrl('edit', ['record' => $delegate, 'approve' => 1]));
        }

        try {
            $approvalService->approve($delegate, auth()->id());
        } catch (ValidationException) {
            return redirect()->to(DelegateResource::getUrl('edit', ['record' => $delegate, 'approve' => 1]));
        }

        return redirect()
            ->to(DelegateResource::getUrl('edit', ['record' => $delegate]))
            ->with('success', __('delegate.notifications.approved.title'));
    }
}
