<?php

declare(strict_types=1);

namespace App\Rules;

use App\Filament\Schemas\DelegateFormFields;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class SeatBelongsToOrganization implements ValidationRule
{
    public function __construct(
        private readonly int|string|null $organizationId
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $seatBelongsToOrganization = DelegateFormFields::seatsOfOrganization($this->organizationId)
            ->whereKey($value)
            ->exists();

        if (! $seatBelongsToOrganization) {
            $fail('validation.exists')->translate();
        }
    }
}
