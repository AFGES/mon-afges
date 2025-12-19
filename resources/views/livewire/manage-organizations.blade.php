<x-filament::section 
    :aside="true" 
    :heading="__('select-organization.title')" 
    :description="__('Gérez vos affiliations aux associations')"
>
    {{-- Display current organizations status --}}
    @if($this->volunteers->isNotEmpty() || $this->delegates->isNotEmpty())
        <div class="mb-6 space-y-4">
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('Mes affiliations actuelles') }}
                </h3>
                
                @if($this->volunteers->isNotEmpty())
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Bénévolat') }}
                        </h4>
                        <ul class="mt-2 space-y-2">
                            @foreach($this->volunteers as $volunteer)
                                <li class="flex items-start gap-2 text-sm">
                                    <span class="mt-0.5">
                                        @if($volunteer->status->value === 'approved')
                                            <x-filament::icon 
                                                icon="heroicon-o-check-circle" 
                                                class="h-5 w-5 text-success-500"
                                            />
                                        @elseif($volunteer->status->value === 'pending')
                                            <x-filament::icon 
                                                icon="heroicon-o-clock" 
                                                class="h-5 w-5 text-warning-500"
                                            />
                                        @else
                                            <x-filament::icon 
                                                icon="heroicon-o-x-circle" 
                                                class="h-5 w-5 text-danger-500"
                                            />
                                        @endif
                                    </span>
                                    <span class="flex-1">
                                        <strong>{{ $volunteer->organization->short_name }}</strong>
                                        - {{ $volunteer->position_title ?? 'Membre' }}
                                        <x-filament::badge :color="$volunteer->status->color()">
                                            {{ $volunteer->status->label() }}
                                        </x-filament::badge>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($this->delegates->isNotEmpty())
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Délégation') }}
                        </h4>
                        <ul class="mt-2 space-y-2">
                            @foreach($this->delegates as $delegate)
                                <li class="flex items-start gap-2 text-sm">
                                    <span class="mt-0.5">
                                        @if($delegate->status->value === 'approved')
                                            <x-filament::icon 
                                                icon="heroicon-o-check-circle" 
                                                class="h-5 w-5 text-success-500"
                                            />
                                        @elseif($delegate->status->value === 'pending')
                                            <x-filament::icon 
                                                icon="heroicon-o-clock" 
                                                class="h-5 w-5 text-warning-500"
                                            />
                                        @else
                                            <x-filament::icon 
                                                icon="heroicon-o-x-circle" 
                                                class="h-5 w-5 text-danger-500"
                                            />
                                        @endif
                                    </span>
                                    <span class="flex-1">
                                        <strong>{{ $delegate->organization->short_name }}</strong>
                                        - {{ $delegate->seat->collegeOrganization->college->name }}, Siège {{ $delegate->seat->seat_number }}
                                        <x-filament::badge :color="$delegate->status->color()">
                                            {{ $delegate->status->label() }}
                                        </x-filament::badge>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Form to add new organizations --}}
    <form wire:submit.prevent="submitOrganizations" class="space-y-6">
        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit">
                {{ __('select-organization.actions.submit.label') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
