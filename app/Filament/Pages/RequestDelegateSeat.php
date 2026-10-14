<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Schemas\DelegateFormFields;
use App\Models\Delegate;
use App\Models\Seat;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * @property-read Schema $form
 */
final class RequestDelegateSeat extends Page
{
    private const array USER_FIELDS = [
        'last_name',
        'first_name',
        'study_program',
        'birth_date',
        'phone',
        'postal_address',
        'elected_mandates',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected string $view = 'filament.pages.request-delegate-seat';

    public static function canAccess(): bool
    {
        return auth()->user()?->canRequestDelegateSeat() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('delegate_form.navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return __('delegate_form.title_without_college');
    }

    public function mount(): void
    {
        $this->form->fill($this->initialFormState());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(fn (Get $get): string => $this->formTitle($get('seat_id')))
                        ->schema([
                            Select::make('organization_id')
                                ->label(__('profile.form.organization.label'))
                                ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                                ->options(fn (): array => $this->eligibleOrganizations())
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (Set $set): mixed => $set('seat_id', null))
                                ->rules([
                                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                        $isEligible = auth()->user()
                                            ->delegateEligibleOrganizations()
                                            ->whereKey($value)
                                            ->exists();

                                        if (! $isEligible) {
                                            $fail(__('validation.exists', ['attribute' => $attribute]));
                                        }
                                    },
                                ])
                                ->validationMessages([
                                    'required' => __('profile.form.organization.required'),
                                ]),
                            Select::make('seat_id')
                                ->label(__('profile.form.seat.label'))
                                ->prefixIcon(Heroicon::OutlinedUserGroup)
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
                                ->live()
                                ->required()
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        $seatBelongsToOrganization = Seat::query()
                                            ->whereKey($value)
                                            ->whereHas(
                                                'collegeOrganization',
                                                fn (Builder $query) => $query->where('organization_id', $get('organization_id')),
                                            )
                                            ->exists();

                                        if (! $seatBelongsToOrganization) {
                                            $fail(__('validation.exists', ['attribute' => $attribute]));
                                        }
                                    },
                                ])
                                ->validationMessages([
                                    'required' => __('profile.form.seat.required'),
                                ]),
                            TextInput::make('email')
                                ->label(__('delegate_form.fields.email'))
                                ->prefixIcon(Heroicon::OutlinedEnvelope)
                                ->helperText(__('profile.form.email.helper'))
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => __('profile.form.email.required'),
                                    'email' => __('profile.form.email.invalid'),
                                ]),
                            DatePicker::make('started_at')
                                ->label(__('delegate_form.fields.started_at'))
                                ->prefixIcon(Heroicon::OutlinedCalendarDays)
                                ->required()
                                ->maxDate(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->validationMessages([
                                    'required' => __('profile.form.started_at.required'),
                                ]),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Section::make(__('delegate_form.sections.general'))
                        ->schema(DelegateFormFields::userComponents(requirePersonalDetails: true))
                        ->columns(2)
                        ->columnSpanFull(),
                    Section::make(__('delegate_form.sections.other_mandates'))
                        ->schema([
                            DelegateFormFields::electedMandatesComponent(),
                        ])
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Actions::make([
                            Action::make('submit')
                                ->label(__('delegate_form.submit'))
                                ->submit('submit'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        DB::transaction(function () use ($data, $user): void {
            $user->update(Arr::only($data, self::USER_FIELDS));

            Delegate::create([
                'user_id' => $user->id,
                'organization_id' => $data['organization_id'],
                'seat_id' => $data['seat_id'],
                'email' => $data['email'],
                'started_at' => $data['started_at'],
            ]);
        });

        Notification::make()
            ->title(__('delegate_form.notifications.submitted.title'))
            ->body(__('delegate_form.notifications.submitted.body'))
            ->success()
            ->send();

        $this->redirect(filament()->getHomeUrl(), navigate: true);
    }

    /**
     * @return array<int|string, string>
     */
    private function eligibleOrganizations(): array
    {
        return auth()->user()->delegateEligibleOrganizations()->pluck('short_name', 'id')->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function initialFormState(): array
    {
        $user = User::query()->findOrFail(auth()->id());
        $eligibleOrganizations = $this->eligibleOrganizations();

        return [
            ...Arr::only($user->attributesToArray(), self::USER_FIELDS),
            'birth_date' => $user->birth_date?->toDateString(),
            'email' => $user->email,
            'started_at' => now()->toDateString(),
            'organization_id' => count($eligibleOrganizations) === 1 ? array_key_first($eligibleOrganizations) : null,
            'seat_id' => null,
        ];
    }

    private function formTitle(mixed $seatId): string
    {
        $collegeName = $seatId === null
            ? null
            : Seat::query()->with('collegeOrganization.college')->find($seatId)?->collegeOrganization?->college?->name;

        return $collegeName === null
            ? __('delegate_form.title_without_college')
            : __('delegate_form.title', ['college' => $collegeName]);
    }
}
