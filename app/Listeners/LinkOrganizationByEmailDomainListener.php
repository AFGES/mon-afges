<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class LinkOrganizationByEmailDomainListener
{
    /**
     * Link a user logging in to the organization owning their email domain.
     *
     * The domain proves membership, so the volunteer is approved right away
     * and the approval-request notification is skipped. The position title is
     * left empty for the user to define before accessing the app.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        // The domain only proves membership once the user owns the address.
        if (! $user->hasVerifiedEmail()) {
            return;
        }

        if (! Str::contains($user->email, '@')) {
            return;
        }

        $domain = Str::of($user->email)->afterLast('@')->lower()->toString();

        if ($domain === '') {
            return;
        }

        // Any earlier link (pending, rejected, ended or deleted) means the user already went through it.
        $organization = Organization::query()
            ->whereNotNull('domain')
            ->whereRaw("LOWER(LTRIM(TRIM(domain), '@')) = ?", [$domain])
            ->whereDoesntHave('volunteers', fn (Builder $query) => $query->withoutGlobalScopes()->where('user_id', $user->id))
            ->first();

        if ($organization === null) {
            return;
        }

        Volunteer::withoutEvents(fn (): Volunteer => Volunteer::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'position_title' => null,
            'started_at' => now(),
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
        ]));
    }
}
