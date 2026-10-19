<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\SubmitVolunteerRequests;
use App\Filament\Schemas\VolunteerRequestFields;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Focused onboarding page for users who are not yet linked to an organization.
 *
 * @property-read Schema $form
 * @property-read Schema $positionTitleForm
 * @property-read Collection<int, Volunteer> $volunteersWithoutPositionTitle
 * @property-read ?Volunteer $pendingOrganizationRequest
 */
final class JoinOrganization extends SimplePage
{
    public const string ROUTE_NAME = 'filament.root.join-organization';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, ?string> */
    public array $positionTitles = [];

    protected static ?string $slug = 'join-organization';

    protected string $view = 'filament.pages.join-organization';

    protected bool $hasTopbar = false;

    public function mount(): void
    {
        if (! $this->user()->needsVolunteerOnboarding()) {
            $this->redirect(Filament::getUrl(), navigate: true);

            return;
        }

        $this->form->fill();
        $this->positionTitleForm->fill();
    }

    public function getTitle(): string
    {
        return __('join_organization.title');
    }

    public function getHeading(): string
    {
        return __('join_organization.title');
    }

    public function getSubheading(): string
    {
        return match (true) {
            $this->volunteersWithoutPositionTitle->isNotEmpty() => __('profile.manage_volunteers.position_title_required.banner'),
            $this->pendingOrganizationRequest !== null => __('join_organization.pending.subheading'),
            default => __('join_organization.request.subheading'),
        };
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
     * The pending request for a new organization, shown while the user waits for its approval.
     */
    public function getPendingOrganizationRequestProperty(): ?Volunteer
    {
        return $this->user()->pendingOrganizationRequest();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                VolunteerRequestFields::organization(
                    fn (): array => Organization::query()->pluck('short_name', 'id')->all()
                ),
                VolunteerRequestFields::positionTitle(),
                VolunteerRequestFields::startedAt(),
            ])
            ->statePath('data');
    }

    public function positionTitleForm(Schema $schema): Schema
    {
        return $schema
            ->components(VolunteerRequestFields::positionTitleInputs($this->volunteersWithoutPositionTitle))
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

        Notification::make()
            ->title(__('profile.manage_volunteers.position_title_required.success_title'))
            ->success()
            ->send();

        if (! $this->user()->needsVolunteerOnboarding()) {
            $this->redirect(Filament::getUrl(), navigate: true);

            return;
        }

        $this->forgetComputedVolunteerState();
        $this->positionTitleForm->fill();
    }

    public function submitRequest(SubmitVolunteerRequests $submitVolunteerRequests): void
    {
        $data = $this->form->getState();

        try {
            $submitVolunteerRequests->handle($this->user(), [$data]);
        } catch (ValidationException $exception) {
            $this->forgetComputedVolunteerState();

            throw ValidationException::withMessages([
                'data.organization_id' => $exception->errors()['organizations'],
            ]);
        }

        $this->forgetComputedVolunteerState();
        $this->form->fill();

        Notification::make()
            ->title(__('volunteer.notifications.pending_approval.title'))
            ->body(__('volunteer.notifications.pending_approval.body'))
            ->info()
            ->send();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    /**
     * Livewire memoizes computed properties for the request, so drop them after a write
     * to render the new state in the same response.
     */
    private function forgetComputedVolunteerState(): void
    {
        unset(
            $this->volunteersWithoutPositionTitle,
            $this->pendingOrganizationRequest,
        );
    }
}
