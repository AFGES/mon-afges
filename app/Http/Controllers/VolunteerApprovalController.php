<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;

final class VolunteerApprovalController extends Controller
{
    public function approve(Volunteer $volunteer): RedirectResponse
    {
        if (! auth()->user()->can('update', $volunteer)) {
            abort(403);
        }

        $volunteer->approve(auth()->user());

        return redirect()
            ->route('filament.root.resources.volunteers.edit', $volunteer)
            ->with('success', __('volunteer.notifications.approved.title'));
    }

    public function reject(Volunteer $volunteer): RedirectResponse
    {
        if (! auth()->user()->can('update', $volunteer)) {
            abort(403);
        }

        $volunteer->reject(auth()->user());

        return redirect()
            ->route('filament.root.resources.volunteers.edit', $volunteer)
            ->with('success', __('volunteer.notifications.rejected.title'));
    }
}
