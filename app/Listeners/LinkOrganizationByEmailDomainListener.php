<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class LinkOrganizationByEmailDomainListener
{
    /**
     * Link a user logging in or verifying their email to the organization owning their email domain.
     *
     * The domain proves membership only once the user owns the address, so unverified
     * users are skipped. Verified covers users verified after their Login event, through
     * Google or the verification link. The volunteer is approved right away and the
     * approval-request notification is skipped. The position title is left empty for
     * the user to define before accessing the app. Any earlier link (pending, rejected,
     * ended or deleted) means the user already went through it, so nothing is created again.
     */
    public function handle(Login|Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

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

        $organization = Organization::query()
            ->where('domain', $domain)
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
