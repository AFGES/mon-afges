<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;

final class VolunteerApprovalController
{
    public function approve(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->approve();

        return redirect()
            ->to(VolunteerResource::getUrl('edit', ['record' => $volunteer]))
            ->with('success', __('volunteer.notifications.approved.title'));
    }

    public function reject(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->reject();

        return redirect()
            ->to(VolunteerResource::getUrl('edit', ['record' => $volunteer]))
            ->with('success', __('volunteer.notifications.rejected.title'));
    }
}
