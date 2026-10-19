<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use App\Models\Seat;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * User fields shared by the seat request form, the admin delegate and user forms and the profile page.
 */
final class DelegateFormFields
{
    /**
     * Personal information stored on the user.
     *
     * @param  bool  $requirePersonalDetails  Makes study program, birth date, phone and postal address mandatory.
     * @return list<TextInput|DatePicker|PhoneInput|Fieldset>
     */
    public static function userComponents(bool $requirePersonalDetails = false): array
    {
        return [
            TextInput::make('last_name')
                ->label(__('delegate_form.fields.last_name'))
                ->prefixIcon(Heroicon::OutlinedUser)
                ->required()
                ->maxLength(255),
            TextInput::make('first_name')
                ->label(__('delegate_form.fields.first_name'))
                ->prefixIcon(Heroicon::OutlinedUser)
                ->required()
                ->maxLength(255),
            ...self::personalDetailComponents($requirePersonalDetails),
        ];
    }

    /**
     * Personal details stored on the user, also editable from the profile page and the admin user form.
     *
     * @param  bool  $requirePersonalDetails  Makes study program, birth date, phone and postal address mandatory.
     * @return list<TextInput|DatePicker|PhoneInput|Fieldset>
     */
    public static function personalDetailComponents(bool $requirePersonalDetails = false): array
    {
        return [
            TextInput::make('study_program')
                ->label(__('user.fields.study_program'))
                ->prefixIcon(Heroicon::OutlinedAcademicCap)
                ->helperText(__('user.fields.study_program_helper'))
                ->maxLength(255)
                ->required($requirePersonalDetails),
            DatePicker::make('birth_date')
                ->label(__('user.fields.birth_date'))
                ->prefixIcon(Heroicon::OutlinedCake)
                ->maxDate(now())
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required($requirePersonalDetails),
            PhoneInput::make('phone')
                ->label(__('user.fields.phone'))
                ->defaultCountry('FR')
                ->initialCountry('FR')
                ->countryOrder(['FR'])
                ->disableLookup()
                ->strictMode()
                ->validateFor()
                ->required($requirePersonalDetails),
            PostalAddressFields::component($requirePersonalDetails),
        ];
    }

    /**
     * Seats attached to the given organization through any of its colleges.
     *
     * @return Builder<Seat>
     */
    public static function seatsOfOrganization(int|string|null $organizationId): Builder
    {
        return self::restrictToOrganization(Seat::query(), $organizationId);
    }

    /**
     * @param  Builder<Seat>  $seats
     * @return Builder<Seat>
     */
    public static function restrictToOrganization(Builder $seats, int|string|null $organizationId): Builder
    {
        return $seats->whereHas(
            'collegeOrganization',
            fn (Builder $query): Builder => $query->where('organization_id', $organizationId),
        );
    }

    /**
     * Rejects disposable email addresses, without any RFC mode: the field already validates the format with `email()`,
     * and the package reports every mode failure as a disposable address.
     *
     * @param  Closure(): ?string|string|null  $exceptEmail  Address always accepted, such as the current email of a grandfathered user.
     */
    public static function rejectDisposableEmail(TextInput $input, Closure|string|null $exceptEmail = null): TextInput
    {
        return $input->rule(
            'disposable_email',
            fn (?string $state): bool => $exceptEmail === null
                || Str::lower(mb_trim((string) $state)) !== Str::lower(mb_trim((string) value($exceptEmail))),
        );
    }

    public static function electedMandatesComponent(): Textarea
    {
        return Textarea::make('elected_mandates')
            ->label(__('user.fields.elected_mandates'))
            ->helperText(__('user.fields.elected_mandates_helper'))
            ->rows(2);
    }
}
