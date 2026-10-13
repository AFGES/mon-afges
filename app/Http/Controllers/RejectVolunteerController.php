<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;

final class RejectVolunteerController
{
    public function __invoke(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->reject();

        return redirect()
            ->to(VolunteerResource::getUrl('edit', ['record' => $volunteer]))
            ->with('success', __('volunteer.notifications.rejected.title'));
    }
}
