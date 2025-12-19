<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Delegate;
use App\Models\Seat;
use App\Models\Volunteer;
use Filament\Actions\Action as PageAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

/**
 * @property-read Schema $form
 */
final class ApplyAsDelegate extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'apply-as-delegate';

    protected static ?string $title = 'Postuler comme délégué';

    protected string $view = 'filament.pages.apply-as-delegate';

    public ?array $data = [];

    public function mount(): void
    {
        // Check if user has volunteer relationships
        $hasVolunteerRelationships = Volunteer::withoutGlobalScopes()
            ->where('user_id', auth()->id())
            ->exists();

        if (! $hasVolunteerRelationships) {
            Notification::make()
                ->title(__('delegate_application.notifications.no_volunteer_relationships.title'))
                ->body(__('delegate_application.notifications.no_volunteer_relationships.body'))
                ->warning()
                ->persistent()
                ->send();
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Form::make([
                    Select::make('organization_id')
                        ->label(__('delegate_application.form.organization.label'))
                        ->helperText(__('delegate_application.form.organization.helper'))
                        ->options($this->getVolunteerOrganizations())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('seat_id', null))
                        ->validationMessages([
                            'required' => __('delegate_application.form.organization.required'),
                        ]),
                    Select::make('seat_id')
                        ->label(__('delegate_application.form.seat.label'))
                        ->helperText(__('delegate_application.form.seat.helper'))
                        ->options(function (callable $get): array {
                            $organizationId = $get('organization_id');
                            if (! $organizationId) {
                                return [];
                            }

                            return $this->getAvailableSeats($organizationId);
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn (callable $get): bool => ! $get('organization_id'))
                        ->validationMessages([
                            'required' => __('delegate_application.form.seat.required'),
                        ]),
                ])
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Actions::make([
                            PageAction::make('submit')
                                ->label(__('delegate_application.form.submit'))
                                ->submit('submit')
                                ->requiresConfirmation()
                                ->modalHeading(__('delegate_application.form.confirm.heading'))
                                ->modalDescription(__('delegate_application.form.confirm.description'))
                                ->modalSubmitActionLabel(__('delegate_application.form.confirm.submit')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Check if user already has an active delegate position for this seat
        $existingDelegate = Delegate::withoutGlobalScopes()
            ->where('user_id', auth()->id())
            ->where('seat_id', $data['seat_id'])
            ->where(function ($query) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', now());
            })
            ->first();

        if ($existingDelegate) {
            Notification::make()
                ->title(__('delegate_application.notifications.already_applied.title'))
                ->body(__('delegate_application.notifications.already_applied.body'))
                ->warning()
                ->send();

            return;
        }

        // Create the delegate application
        Delegate::create([
            'user_id' => auth()->id(),
            'organization_id' => $data['organization_id'],
            'seat_id' => $data['seat_id'],
            'started_at' => now(),
            'ended_at' => null,
        ]);

        Notification::make()
            ->title(__('delegate_application.notifications.success.title'))
            ->body(__('delegate_application.notifications.success.body'))
            ->success()
            ->send();

        // Redirect to the panel home
        $this->redirect(Filament::getUrl());
    }

    public function getVolunteerOrganizations(): array
    {
        return Volunteer::withoutGlobalScopes()
            ->where('user_id', auth()->id())
            ->with('organization')
            ->get()
            ->pluck('organization.short_name', 'organization.id')
            ->unique()
            ->toArray();
    }

    public function getAvailableSeats(int $organizationId): array
    {
        return Seat::query()
            ->whereHas('collegeOrganization', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->with('collegeOrganization.college')
            ->get()
            ->mapWithKeys(fn (Seat $seat) => [$seat->id => $seat->label()])
            ->toArray();
    }
}
