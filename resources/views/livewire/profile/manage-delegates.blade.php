<x-filament::section 
    :aside="true" 
    :heading="__('profile.manage_delegates.section_heading')" 
    :description="__('profile.manage_delegates.section_description')"
>
    {{-- Display current delegate applications status --}}
    @if($this->delegates->isNotEmpty())
        <div class="mb-6">
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('profile.manage_delegates.my_applications') }}
                </h3>
                
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
                                - {{ $delegate->seat->collegeOrganization->college->name }}, {{ __('profile.manage_delegates.seat_label', ['number' => $delegate->seat->seat_number]) }}
                                <x-filament::badge :color="$delegate->status->color()">
                                    {{ $delegate->status->label() }}
                                </x-filament::badge>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Form to add new delegate applications --}}
    <form wire:submit.prevent="submitDelegates" class="space-y-6">
        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit">
                {{ __('profile.manage_delegates.submit_button') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
