<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\Volunteer;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

final class ManageOrganizations extends MyProfileComponent
{
    protected string $view = 'livewire.manage-organizations';

    public array $data = [];

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
    }

    public function getVolunteersProperty()
    {
        return Volunteer::withoutGlobalScopes()
            ->where('user_id', auth()->id())
            ->with('organization')
            ->orderBy('started_at', 'desc')
            ->get();
    }

    public function getDelegatesProperty()
    {
        return Delegate::withoutGlobalScopes()
            ->where('user_id', auth()->id())
            ->with(['organization', 'seat.collegeOrganization.college'])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('organizations')
                    ->label(__('select-organization.form.organizations.label'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('select-organization.form.organization.label'))
                            ->options(Organization::query()->pluck('short_name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->validationMessages([
                                'required' => __('select-organization.form.organization.required'),
                            ]),
                        TextInput::make('position_title')
                            ->label(__('select-organization.form.position_title.label'))
                            ->placeholder(__('select-organization.form.position_title.placeholder'))
                            ->default('Membre')
                            ->maxLength(255),
                        DatePicker::make('started_at')
                            ->label(__('select-organization.form.started_at.label'))
                            ->default(now())
                            ->required()
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->validationMessages([
                                'required' => __('select-organization.form.started_at.required'),
                            ]),
                        Checkbox::make('apply_as_delegate')
                            ->label(__('select-organization.form.apply_as_delegate.label'))
                            ->helperText(__('select-organization.form.apply_as_delegate.helper'))
                            ->live()
                            ->default(false),
                        Select::make('seat_id')
                            ->label(__('select-organization.form.seat.label'))
                            ->helperText(__('select-organization.form.seat.helper'))
                            ->options(function (callable $get) {
                                $organizationId = $get('organization_id');
                                if (! $organizationId) {
                                    return [];
                                }

                                return Seat::query()
                                    ->whereHas('collegeOrganization', function ($query) use ($organizationId) {
                                        $query->where('organization_id', $organizationId);
                                    })
                                    ->with(['collegeOrganization.college', 'collegeOrganization.organization'])
                                    ->get()
                                    ->mapWithKeys(function ($seat) {
                                        return [
                                            $seat->id => sprintf(
                                                __('profile.manage_delegates.seat_format'),
                                                $seat->collegeOrganization->organization->short_name,
                                                $seat->collegeOrganization->college->name,
                                                $seat->seat_number
                                            ),
                                        ];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('apply_as_delegate') === true)
                            ->required(fn (callable $get) => $get('apply_as_delegate') === true)
                            ->validationMessages([
                                'required' => __('select-organization.form.seat.required'),
                            ]),
                    ])
                    ->columns(3)
                    ->minItems(0)
                    ->addActionLabel(__('select-organization.form.organizations.add_action'))
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => Organization::find($state['organization_id'] ?? null)?->short_name ?? __('select-organization.form.organizations.new_item'))
                    ->defaultItems(0),
            ])
            ->statePath('data');
    }

    public function submitOrganizations(): void
    {
        $data = $this->form->getState();

        if (! isset($data['organizations']) || empty($data['organizations'])) {
            return;
        }

        $user = auth()->user();

        $hasVolunteerApplications = false;
        $hasDelegateApplications = false;

        foreach ($data['organizations'] as $item) {
            // Create volunteer application
            Volunteer::create([
                'user_id' => $user->id,
                'organization_id' => $item['organization_id'],
                'position_title' => $item['position_title'] ?? 'Membre',
                'started_at' => $item['started_at'] ?? now(),
            ]);
            $hasVolunteerApplications = true;

            // Create delegate application if requested
            if (($item['apply_as_delegate'] ?? false) && isset($item['seat_id'])) {
                Delegate::create([
                    'user_id' => $user->id,
                    'organization_id' => $item['organization_id'],
                    'seat_id' => $item['seat_id'],
                    'started_at' => $item['started_at'] ?? now(),
                ]);
                $hasDelegateApplications = true;
            }
        }

        // Reset the organizations field
        $this->data['organizations'] = [];
        $this->form->fill();

        // Show appropriate notification based on what was submitted
        if ($hasDelegateApplications && $hasVolunteerApplications) {
            Notification::make()
                ->title(__('select-organization.notifications.volunteer_and_delegate_submitted.title'))
                ->body(__('select-organization.notifications.volunteer_and_delegate_submitted.body'))
                ->info()
                ->send();
        } elseif ($hasDelegateApplications) {
            Notification::make()
                ->title(__('select-organization.notifications.delegate_submitted.title'))
                ->body(__('select-organization.notifications.delegate_submitted.body'))
                ->info()
                ->send();
        } else {
            Notification::make()
                ->title(__('volunteer.notifications.pending_approval.title'))
                ->body(__('volunteer.notifications.pending_approval.body'))
                ->info()
                ->send();
        }
    }
}
