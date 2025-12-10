<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Organization;
use App\Models\Volunteer;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
final class SelectOrganization extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'select-organization';

    protected string $view = 'filament.pages.select-organization';

    public ?array $data = [];

    public function mount(): void
    {
        // If no organizations exist, show a warning and skip the form
        if (Organization::query()->doesntExist()) {
            Notification::make()
                ->title(__('select-organization.notifications.no_organizations.title'))
                ->body(__('select-organization.notifications.no_organizations.body'))
                ->warning()
                ->persistent()
                ->send();
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
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
                                ->validationMessages([
                                    'required' => __('select-organization.form.organization.required'),
                                ]),
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
                        ])
                        ->columns(2)
                        ->required()
                        ->minItems(1)
                        ->addActionLabel(__('select-organization.form.organizations.add_action'))
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => Organization::find($state['organization_id'] ?? null)?->short_name ?? __('select-organization.form.organizations.new_item'))
                        ->defaultItems(0)
                        ->validationMessages([
                            'required' => __('select-organization.form.organizations.required'),
                            'min' => __('select-organization.form.organizations.min'),
                        ]),
                ])
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Actions::make([
                            Action::make('submit')
                                ->label(__('select-organization.actions.submit.label'))
                                ->submit('submit')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $user = Auth::user();

        foreach ($data['organizations'] as $item) {
            Volunteer::create([
                'user_id' => $user->id,
                'organization_id' => $item['organization_id'],
                'position_title' => 'Member',
                'started_at' => $item['started_at'] ?? now(),
            ]);
        }

        Notification::make()
            ->title(__('select-organization.notifications.success.title'))
            ->body(__('select-organization.notifications.success.body'))
            ->success()
            ->send();

        $this->redirect(Filament::getUrl());
    }

    public function getTitle(): string
    {
        return __('select-organization.title');
    }
}
