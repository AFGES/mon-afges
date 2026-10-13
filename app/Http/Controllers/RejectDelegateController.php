<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use Illuminate\Http\RedirectResponse;

final class RejectDelegateController
{
    public function __invoke(Delegate $delegate): RedirectResponse
    {
        $delegate->reject();

        return redirect()
            ->to(DelegateResource::getUrl('edit', ['record' => $delegate]))
            ->with('success', __('delegate.notifications.rejected.title'));
    }
}
