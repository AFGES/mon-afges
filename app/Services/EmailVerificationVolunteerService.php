<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\VolunteerStatus;
use App\Models\Email;
use App\Models\Organization;
use App\Models\Volunteer;

final class EmailVerificationVolunteerService
{
    /**
     * Check if email domain matches an organization and create volunteer relationship.
     */
    public function handleVerifiedEmail(Email $email): ?Volunteer
    {
        $domain = $this->extractDomain($email->email);

        if (! $domain) {
            return null;
        }

        $organization = Organization::whereRaw('LOWER(domain) = ?', [$domain])->first();

        if (! $organization) {
            return null;
        }

        // Check if volunteer relationship already exists
        $existingVolunteer = Volunteer::where('user_id', $email->user_id)
            ->where('organization_id', $organization->id)
            ->whereNull('ended_at')
            ->first();

        if ($existingVolunteer) {
            return $existingVolunteer;
        }

        // Create new volunteer relationship
        return Volunteer::create([
            'user_id' => $email->user_id,
            'organization_id' => $organization->id,
            'position_title' => 'Membre',
            'started_at' => now(),
            'status' => VolunteerStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    /**
     * Extract domain from email address.
     */
    private function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);

        return isset($parts[1]) ? strtolower($parts[1]) : null;
    }
}
