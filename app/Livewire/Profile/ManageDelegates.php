<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

final class ManageDelegates extends MyProfileComponent
{
    /** @var array<string, mixed> */
    public array $data = [];

    protected string $view = 'livewire.profile.manage-delegates';

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

    /**
     * @return Collection<int, Delegate>
     */
    public function getDelegatesProperty(): Collection
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
                        TextInput::make('email')
                            ->label(__('profile.form.email.label'))
                            ->helperText(__('profile.form.email.helper'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->default(fn (): string => auth()->user()->email)
                            ->validationMessages([
                                'required' => __('profile.form.email.required'),
                                'email' => __('profile.form.email.invalid'),
                            ]),
                        Select::make('seat_id')
                            ->label(__('profile.form.seat.label'))
                            ->helperText(__('profile.form.seat.helper'))
                            ->options(function (Get $get) {
                                $organizationId = $get('organization_id');
                                if (! $organizationId) {
                                    return [];
                                }

                                return Seat::query()
                                    ->whereHas('collegeOrganization', function (Builder $query) use ($organizationId) {
                                        $query->where('organization_id', $organizationId);
                                    })
                                    ->with(['collegeOrganization.college', 'collegeOrganization.organization'])
                                    ->get()
                                    ->mapWithKeys(fn (Seat $seat) => [$seat->id => $seat->label()]);
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

        if (empty($data['applications'])) {
            return;
        }

        $user = auth()->user();

        foreach ($data['applications'] as $item) {
            Delegate::create([
                'user_id' => $user->id,
                'organization_id' => $item['organization_id'],
                'seat_id' => $item['seat_id'],
                'email' => $item['email'],
                'started_at' => $item['started_at'] ?? now(),
            ]);
        }

        $this->data['applications'] = [];
        $this->form->fill();

        Notification::make()
            ->title(__('profile.manage_delegates.notification_title'))
            ->body(__('profile.manage_delegates.notification_body'))
            ->info()
            ->send();
    }

    public function terminateDelegate(string $delegateId): void
    {
        $delegate = Delegate::withoutGlobalScopes()
            ->where('id', $delegateId)
            ->firstOrFail();

        if (! auth()->user()->can('terminate', $delegate)) {
            Notification::make()
                ->title(__('profile.manage_delegates.terminate.not_authorized_title'))
                ->body(__('profile.manage_delegates.terminate.not_authorized_body'))
                ->warning()
                ->send();

            return;
        }

        $delegate->end();

        Notification::make()
            ->title(__('profile.manage_delegates.terminate.success_title'))
            ->body(__('profile.manage_delegates.terminate.success_body'))
            ->success()
            ->send();
    }
}
