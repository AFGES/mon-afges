<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Volunteer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;

final class CanRequestOrganization implements ValidationRule
{
    /**
     * @param  Collection<int, string>  $linkedOrganizationIds  see User::linkedOrganizationIds()
     * @param  ?Volunteer  $pendingRequest  see User::pendingOrganizationRequest()
     */
    public function __construct(
        private readonly Collection $linkedOrganizationIds,
        private readonly ?Volunteer $pendingRequest,
    ) {}

    /**
     * Run the validation rule.
     *
     * A pending request for a new organization blocks requests for other new
     * organizations. Organizations the user is already linked to stay allowed.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if ($this->linkedOrganizationIds->contains($value)) {
            return;
        }

        if ($this->pendingRequest === null) {
            return;
        }

        $fail('profile.manage_volunteers.request_locked.message')->translate([
            'organization' => $this->pendingRequest->organization->short_name,
        ]);
    }
}
