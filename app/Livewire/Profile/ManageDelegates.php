<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

final class ManageDelegates extends MyProfileComponent
{
    protected string $view = 'livewire.profile.manage-delegates';

    public array $data = [];

    public static function getSort(): int
    {
        return 110;
    }

    public function mount(): void
    {
        $this->data = [
            'applications' => [],
        ];
        $this->form->fill($this->data);
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
                Repeater::make('applications')
                    ->label(__('profile.manage_delegates.label'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('profile.form.organization.label'))
                            ->options(Organization::query()->pluck('short_name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->validationMessages([
                                'required' => __('profile.form.organization.required'),
                            ]),
                        Select::make('seat_id')
                            ->label(__('profile.form.seat.label'))
                            ->helperText(__('profile.form.seat.helper'))
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
                            ->required()
                            ->validationMessages([
                                'required' => __('profile.form.seat.required'),
                            ]),
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
                    ->addActionLabel(__('profile.manage_delegates.add_action'))
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => Organization::find($state['organization_id'] ?? null)?->short_name ?? __('profile.manage_delegates.new_item'))
                    ->defaultItems(0),
            ])
            ->statePath('data');
    }

    public function submitDelegates(): void
    {
        $data = $this->form->getState();

        if (! isset($data['applications']) || empty($data['applications'])) {
            return;
        }

        $user = auth()->user();

        foreach ($data['applications'] as $item) {
            Delegate::create([
                'user_id' => $user->id,
                'organization_id' => $item['organization_id'],
                'seat_id' => $item['seat_id'],
                'started_at' => $item['started_at'] ?? now(),
            ]);
        }

        // Reset the applications field
        $this->data['applications'] = [];
        $this->form->fill();

        Notification::make()
            ->title(__('profile.manage_delegates.notification_title'))
            ->body(__('profile.manage_delegates.notification_body'))
            ->info()
            ->send();
    }
}
