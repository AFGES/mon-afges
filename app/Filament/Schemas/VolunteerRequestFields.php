<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use App\Models\Volunteer;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Volunteer request fields shared by the profile page and the join organization page.
 */
final class VolunteerRequestFields
{
    /**
     * @param  array<string, string>|Closure(): array<string, string>  $options
     */
    public static function organization(array|Closure $options): Select
    {
        return Select::make('organization_id')
            ->label(__('profile.form.organization.label'))
            ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
            ->options($options)
            ->required()
            ->searchable()
            ->preload()
            ->validationMessages([
                'required' => __('profile.form.organization.required'),
            ]);
    }

    public static function positionTitle(): TextInput
    {
        return TextInput::make('position_title')
            ->label(__('profile.form.position_title.label'))
            ->prefixIcon(Heroicon::OutlinedBriefcase)
            ->placeholder(__('profile.form.position_title.placeholder'))
            ->required()
            ->maxLength(255)
            ->validationMessages([
                'required' => __('profile.form.position_title.required'),
            ]);
    }

    public static function startedAt(): DatePicker
    {
        return DatePicker::make('started_at')
            ->label(__('profile.form.started_at.label'))
            ->prefixIcon(Heroicon::OutlinedCalendarDays)
            ->default(now())
            ->required()
            ->maxDate(now())
            ->native(false)
            ->displayFormat('d/m/Y')
            ->validationMessages([
                'required' => __('profile.form.started_at.required'),
            ]);
    }

    /**
     * One position title input per approved volunteer still waiting for its title, keyed by volunteer id.
     *
     * @param  Collection<int, Volunteer>  $volunteers  with their organization loaded
     * @return list<TextInput>
     */
    public static function positionTitleInputs(Collection $volunteers): array
    {
        return $volunteers
            ->map(fn (Volunteer $volunteer): TextInput => TextInput::make($volunteer->id)
                ->label(__('profile.manage_volunteers.position_title_required.field_label', [
                    'organization' => $volunteer->organization->short_name,
                ]))
                ->prefixIcon(Heroicon::OutlinedBriefcase)
                ->required()
                ->maxLength(255)
                ->validationMessages([
                    'required' => __('profile.form.position_title.required'),
                ]))
            ->values()
            ->all();
    }
}
