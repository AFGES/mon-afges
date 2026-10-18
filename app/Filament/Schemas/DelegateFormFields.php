<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use App\Models\Seat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fields of the "Fiche Délégué-e" shared by the seat request form and the admin delegate form.
 */
final class DelegateFormFields
{
    /**
     * Personal information stored on the user.
     *
     * @param  bool  $requirePersonalDetails  Makes study program, birth date, phone and postal address mandatory.
     * @return list<TextInput|DatePicker|Textarea>
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
            TextInput::make('study_program')
                ->label(__('delegate_form.fields.study_program'))
                ->prefixIcon(Heroicon::OutlinedAcademicCap)
                ->helperText(__('delegate_form.fields.study_program_helper'))
                ->maxLength(255)
                ->required($requirePersonalDetails),
            DatePicker::make('birth_date')
                ->label(__('delegate_form.fields.birth_date'))
                ->prefixIcon(Heroicon::OutlinedCake)
                ->maxDate(now())
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required($requirePersonalDetails),
            TextInput::make('phone')
                ->label(__('delegate_form.fields.phone'))
                ->prefixIcon(Heroicon::OutlinedPhone)
                ->tel()
                ->maxLength(30)
                ->required($requirePersonalDetails),
            Textarea::make('postal_address')
                ->label(__('delegate_form.fields.postal_address'))
                ->rows(2)
                ->required($requirePersonalDetails),
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

    public static function electedMandatesComponent(): Textarea
    {
        return Textarea::make('elected_mandates')
            ->label(__('delegate_form.fields.elected_mandates'))
            ->helperText(__('delegate_form.fields.elected_mandates_helper'))
            ->rows(2);
    }
}
