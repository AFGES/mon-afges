<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Delegate;
use Illuminate\Http\RedirectResponse;

final class DelegateApprovalController extends Controller
{
    public function approve(Delegate $delegate): RedirectResponse
    {
        if (! auth()->user()->can('update', $delegate)) {
            abort(403);
        }

        $delegate->approve(auth()->user());

        return redirect()
            ->route('filament.root.resources.delegates.edit', $delegate)
            ->with('success', __('delegate.notifications.approved.title'));
    }

    public function reject(Delegate $delegate): RedirectResponse
    {
        if (! auth()->user()->can('update', $delegate)) {
            abort(403);
        }

        $delegate->reject(auth()->user());

        return redirect()
            ->route('filament.root.resources.delegates.edit', $delegate)
            ->with('success', __('delegate.notifications.rejected.title'));
    }
}
