<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\Organization;
use App\Models\Volunteer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

final class ManageVolunteers extends MyProfileComponent
{
    protected string $view = 'livewire.profile.manage-volunteers';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('organizations')
                    ->label(__('profile.manage_volunteers.form_label'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('profile.form.organization.label'))
                            ->options(Organization::query()->pluck('short_name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->validationMessages([
                                'required' => __('profile.form.organization.required'),
                            ]),
                        TextInput::make('position_title')
                            ->label(__('profile.form.position_title.label'))
                            ->placeholder(__('profile.form.position_title.placeholder'))
                            ->default('Membre')
                            ->maxLength(255),
                        DatePicker::make('started_at')
                            ->label(__('profile.form.started_at.label'))
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
                    ->addActionLabel(__('profile.manage_volunteers.add_action'))
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => Organization::find($state['organization_id'] ?? null)?->short_name ?? __('profile.manage_volunteers.new_item'))
                    ->defaultItems(0),
            ])
            ->statePath('data');
    }

    public function submitVolunteers(): void
    {
        $data = $this->form->getState();

        if (! isset($data['organizations']) || empty($data['organizations'])) {
            return;
        }

        $user = auth()->user();

        foreach ($data['organizations'] as $item) {
            Volunteer::create([
                'user_id' => $user->id,
                'organization_id' => $item['organization_id'],
                'position_title' => $item['position_title'] ?? __('profile.form.position_title.placeholder'),
                'started_at' => $item['started_at'] ?? now(),
            ]);
        }

        // Reset the organizations field
        $this->data['organizations'] = [];
        $this->form->fill();

        Notification::make()
            ->title(__('volunteer.notifications.pending_approval.title'))
            ->body(__('volunteer.notifications.pending_approval.body'))
            ->info()
            ->send();
    }

    public function terminateVolunteer(int $volunteerId): void
    {
        $volunteer = Volunteer::withoutGlobalScopes()
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

        Notification::make()
            ->title(__('profile.manage_volunteers.terminate.success_title'))
            ->body(__('profile.manage_volunteers.terminate.success_body'))
            ->success()
            ->send();
    }
}
