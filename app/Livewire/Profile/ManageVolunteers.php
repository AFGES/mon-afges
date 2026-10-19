<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\Organization;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\User;
use App\Models\Volunteer;
use App\Rules\CanRequestOrganization;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection as SupportCollection;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

/**
 * @property-read Schema $positionTitleForm
 * @property-read Collection<int, Volunteer> $volunteers
 * @property-read SupportCollection<int, string> $linkedOrganizationIds
 * @property-read ?Volunteer $pendingOrganizationRequest
 * @property-read Collection<int, Volunteer> $volunteersWithoutPositionTitle
 * @property-read bool $canAddVolunteers
 */
final class ManageVolunteers extends MyProfileComponent
{
    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, ?string> */
    public array $positionTitles = [];

    protected string $view = 'livewire.profile.manage-volunteers';

    public static function getSort(): int
    {
        return 100;
    }

    public function mount(): void
    {
        $this->data = [
            'organizations' => [],
        ];
        $this->form->fill($this->data);
        $this->positionTitleForm->fill();
    }

    /**
     * @return Collection<int, Volunteer>
     */
    public function getVolunteersProperty(): Collection
    {
        return Volunteer::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])
            ->where('user_id', auth()->id())
            ->with(['organization' => fn (BelongsTo $query) => $query->withTrashed()])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Organizations the user is linked to, computed once per request for options and validation.
     *
     * @return SupportCollection<int, string>
     */
    public function getLinkedOrganizationIdsProperty(): SupportCollection
    {
        return $this->user()->linkedOrganizationIds();
    }

    /**
     * The pending request for a new organization, which locks requests for other new organizations.
     */
    public function getPendingOrganizationRequestProperty(): ?Volunteer
    {
        return $this->user()->pendingOrganizationRequest($this->linkedOrganizationIds);
    }

    /**
     * Approved, current volunteers of the user still waiting for a position title.
     *
     * @return Collection<int, Volunteer>
     */
    public function getVolunteersWithoutPositionTitleProperty(): Collection
    {
        return $this->user()->volunteers()
            ->awaitingPositionTitle()
            ->with('organization')
            ->get();
    }

    /**
     * A locked user without any linked organization has nothing left to request.
     */
    public function getCanAddVolunteersProperty(): bool
    {
        return $this->pendingOrganizationRequest === null
            || $this->linkedOrganizationIds->isNotEmpty();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('organizations')
                    ->label(__('profile.manage_volunteers.form_label'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('profile.form.organization.label'))
                            ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                            ->options($this->requestableOrganizationOptions(...))
                            ->required()
                            ->rule(fn (): CanRequestOrganization => new CanRequestOrganization(
                                $this->linkedOrganizationIds,
                                $this->pendingOrganizationRequest,
                            ))
                            ->searchable()
                            ->preload()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->validationMessages([
                                'required' => __('profile.form.organization.required'),
                            ]),
                        TextInput::make('position_title')
                            ->label(__('profile.form.position_title.label'))
                            ->prefixIcon(Heroicon::OutlinedBriefcase)
                            ->placeholder(__('profile.form.position_title.placeholder'))
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => __('profile.form.position_title.required'),
                            ]),
                        DatePicker::make('started_at')
                            ->label(__('profile.form.started_at.label'))
                            ->prefixIcon(Heroicon::OutlinedCalendarDays)
                            ->default(now())
                            ->required()
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->validationMessages([
                                'required' => __('profile.form.started_at.required'),
                            ]),
                    ])
                    ->columns(3)
                    ->minItems(0)
                    ->rule($this->singleNewOrganizationRule(...))
                    ->addable(fn (): bool => $this->canAddVolunteers)
                    ->addActionLabel(__('profile.manage_volunteers.add_action'))
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => Organization::find($state['organization_id'] ?? null)?->short_name ?? __('profile.manage_volunteers.new_item'))
                    ->defaultItems(0),
            ])
            ->statePath('data');
    }

    public function positionTitleForm(Schema $schema): Schema
    {
        return $schema
            ->components(
                $this->volunteersWithoutPositionTitle
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
                    ->all()
            )
            ->statePath('positionTitles');
    }

    public function definePositionTitles(): void
    {
        $titles = $this->positionTitleForm->getState();

        $volunteers = $this->user()->volunteers()
            ->awaitingPositionTitle()
            ->whereKey(array_keys($titles))
            ->get();

        foreach ($volunteers as $volunteer) {
            $volunteer->update(['position_title' => $titles[$volunteer->id]]);
        }

        $this->forgetComputedVolunteerState();
        $this->positionTitleForm->fill();

        Notification::make()
            ->title(__('profile.manage_volunteers.position_title_required.success_title'))
            ->success()
            ->send();
    }

    public function submitVolunteers(): void
    {
        $data = $this->form->getState();

        if (empty($data['organizations'])) {
            return;
        }

        $user = $this->user();

        foreach ($data['organizations'] as $item) {
            Volunteer::create([
                'user_id' => $user->id,
                'organization_id' => $item['organization_id'],
                'position_title' => $item['position_title'],
                'started_at' => $item['started_at'] ?? now(),
            ]);
        }

        $this->forgetComputedVolunteerState();
        $this->data['organizations'] = [];
        $this->form->fill();

        Notification::make()
            ->title(__('volunteer.notifications.pending_approval.title'))
            ->body(__('volunteer.notifications.pending_approval.body'))
            ->info()
            ->send();
    }

    public function terminateVolunteer(string $volunteerId): void
    {
        $volunteer = Volunteer::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])
            ->where('id', $volunteerId)
            ->firstOrFail();

        if (! auth()->user()->can('terminate', $volunteer)) {
            Notification::make()
                ->title(__('profile.manage_volunteers.terminate.not_authorized_title'))
                ->body(__('profile.manage_volunteers.terminate.not_authorized_body'))
                ->warning()
                ->send();

            return;
        }

        $volunteer->end();

        $this->forgetComputedVolunteerState();

        Notification::make()
            ->title(__('profile.manage_volunteers.terminate.success_title'))
            ->body(__('profile.manage_volunteers.terminate.success_body'))
            ->success()
            ->send();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    /**
     * Livewire memoizes computed properties for the request, so drop them after a write
     * to render the new lock and title state in the same response.
     */
    private function forgetComputedVolunteerState(): void
    {
        unset(
            $this->volunteers,
            $this->linkedOrganizationIds,
            $this->pendingOrganizationRequest,
            $this->volunteersWithoutPositionTitle,
            $this->canAddVolunteers,
        );
    }

    /**
     * While a request for a new organization is pending, only linked organizations can be chosen.
     *
     * @return array<string, string>
     */
    private function requestableOrganizationOptions(): array
    {
        return Organization::query()
            ->when(
                $this->pendingOrganizationRequest !== null,
                fn (Builder $query) => $query->whereKey($this->linkedOrganizationIds),
            )
            ->pluck('short_name', 'id')
            ->all();
    }

    /**
     * Rejects a submission requesting more than one organization the user is not linked to.
     *
     * @return Closure(string, mixed, Closure): void
     */
    private function singleNewOrganizationRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $linkedOrganizationIds = $this->linkedOrganizationIds;

            $newOrganizationCount = collect(is_array($value) ? $value : [])
                ->pluck('organization_id')
                ->filter()
                ->reject(fn (string $organizationId): bool => $linkedOrganizationIds->contains($organizationId))
                ->unique()
                ->count();

            if ($newOrganizationCount > 1) {
                $fail(__('profile.manage_volunteers.request_locked.single_new_organization'));
            }
        };
    }
}
