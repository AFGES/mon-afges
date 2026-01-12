<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Seat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProxyDelegatesFromSameCollege implements ValidationRule
{
    public function __construct(
        private readonly int $delegatorSeatId
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $delegatorSeat = Seat::find($this->delegatorSeatId);
        $proxyHolderSeat = Seat::find($value);

        if (! $delegatorSeat || ! $proxyHolderSeat) {
            $fail('validation.exists')->translate();

            return;
        }

        $delegatorCollegeId = $delegatorSeat->collegeOrganization->college_id;
        $proxyHolderCollegeId = $proxyHolderSeat->collegeOrganization->college_id;

        if ($delegatorCollegeId !== $proxyHolderCollegeId) {
            $fail('Le siège mandataire doit appartenir au même collège que le siège délégateur.');
        }
    }
}
