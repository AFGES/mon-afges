<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Auth\Events\Registered;
use Illuminate\Support\Str;

final class AssignOrganizationOnRegistrationListener
{
    /**
     * Link a newly registered user to the organization owning their email domain.
     *
     * The domain proves membership, so the volunteer is approved right away
     * and the approval-request notification is skipped.
     */
    public function handle(Registered $event): void
    {
        $user = $event->getUser();

        if (! $user instanceof User) {
            return;
        }

        if (! Str::contains($user->email, '@')) {
            return;
        }

        $domain = Str::of($user->email)->afterLast('@')->lower()->toString();

        if ($domain === '') {
            return;
        }

        $organization = Organization::query()
            ->whereNotNull('domain')
            ->whereRaw("LOWER(LTRIM(TRIM(domain), '@')) = ?", [$domain])
            ->first();

        if ($organization === null) {
            return;
        }

        $alreadyLinked = Volunteer::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        Volunteer::withoutEvents(fn (): Volunteer => Volunteer::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'position_title' => __('profile.form.position_title.placeholder'),
            'started_at' => now(),
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
        ]));
    }
}
