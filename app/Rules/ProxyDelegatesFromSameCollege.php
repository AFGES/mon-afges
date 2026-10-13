<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\College;
use App\Models\Seat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ProxyDelegatesFromSameCollege implements ValidationRule
{
    public function __construct(
        private readonly ?string $delegatorSeatId
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If delegator seat ID is not set, skip validation (required rule will catch it)
        if (! $this->delegatorSeatId) {
            return;
        }

        $delegatorSeat = Seat::find($this->delegatorSeatId);
        $proxyHolderSeat = Seat::find($value);

        if (! $delegatorSeat || ! $proxyHolderSeat) {
            $fail('validation.exists')->translate();

            return;
        }

        $delegatorCollegeId = $delegatorSeat->collegeOrganization->college_id;
        $proxyHolderCollegeId = $proxyHolderSeat->collegeOrganization->college_id;

        $delegatorCollege = College::find($delegatorCollegeId);

        if (! $delegatorCollege) {
            $fail('validation.exists')->translate();

            return;
        }

        if (! $delegatorCollege->require_same_college_for_proxy) {
            $proxyHolderCollege = College::find($proxyHolderCollegeId);

            if (! $proxyHolderCollege || ! $proxyHolderCollege->can_vote) {
                $fail('Le siège mandataire doit appartenir à un collège délibérant.');
            }

            return;
        }

        if ($delegatorCollegeId !== $proxyHolderCollegeId) {
            $fail('Le siège mandataire doit appartenir au même collège que le siège délégateur.');
        }
    }
}
