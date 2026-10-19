<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitVolunteerRequests
{
    /**
     * Create pending volunteer requests, enforcing at most one new organization at a time.
     *
     * The user row is locked so concurrent submissions see each other's requests:
     * the form validation alone reads the pending request before the write.
     *
     * @param  list<array{organization_id: string, position_title: string, started_at?: ?string}>  $requests
     *
     * @throws ValidationException When the requests break the one-new-organization rule.
     */
    public function handle(User $user, array $requests): void
    {
        DB::transaction(function () use ($user, $requests): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->sole();

            $linkedOrganizationIds = $user->linkedOrganizationIds();

            $newOrganizationIds = collect($requests)
                ->pluck('organization_id')
                ->reject(fn (string $organizationId): bool => $linkedOrganizationIds->contains($organizationId))
                ->unique();

            if ($newOrganizationIds->isNotEmpty()) {
                $pendingRequest = $user->pendingOrganizationRequest($linkedOrganizationIds);

                if ($pendingRequest !== null) {
                    throw ValidationException::withMessages([
                        'organizations' => __('profile.manage_volunteers.request_locked.message', [
                            'organization' => $pendingRequest->organization->short_name,
                        ]),
                    ]);
                }
            }

            if ($newOrganizationIds->count() > 1) {
                throw ValidationException::withMessages([
                    'organizations' => __('profile.manage_volunteers.request_locked.single_new_organization'),
                ]);
            }

            foreach ($requests as $request) {
                Volunteer::create([
                    'user_id' => $user->id,
                    'organization_id' => $request['organization_id'],
                    'position_title' => $request['position_title'],
                    'started_at' => $request['started_at'] ?? now(),
                ]);
            }
        });
    }
}
