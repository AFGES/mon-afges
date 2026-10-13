<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use Illuminate\Http\RedirectResponse;

final class DelegateApprovalController
{
    public function approve(Delegate $delegate): RedirectResponse
    {
        $delegate->approve();

        return redirect()
            ->to(DelegateResource::getUrl('edit', ['record' => $delegate]))
            ->with('success', __('delegate.notifications.approved.title'));
    }

    public function reject(Delegate $delegate): RedirectResponse
    {
        $delegate->reject();

        return redirect()
            ->to(DelegateResource::getUrl('edit', ['record' => $delegate]))
            ->with('success', __('delegate.notifications.rejected.title'));
    }
}
