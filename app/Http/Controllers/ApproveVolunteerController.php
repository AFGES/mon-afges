<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;

final class ApproveVolunteerController
{
    public function __invoke(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->approve();

        return redirect()
            ->to(VolunteerResource::getUrl('edit', ['record' => $volunteer]))
            ->with('success', __('volunteer.notifications.approved.title'));
    }
}
