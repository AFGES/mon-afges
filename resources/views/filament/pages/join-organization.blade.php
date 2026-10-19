<x-filament-panels::page.simple>
    @if($this->volunteersWithoutPositionTitle->isNotEmpty())
        <form wire:submit.prevent="definePositionTitles" class="space-y-6">
            {{ $this->positionTitleForm }}

            <x-filament::button type="submit" class="w-full">
                {{ __('profile.form.submit.label') }}
            </x-filament::button>
        </form>
    @elseif($this->pendingOrganizationRequest)
        <div class="rounded-lg bg-warning-50 p-4 text-sm text-warning-700 ring-1 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30">
            <div class="flex items-start gap-2">
                <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 shrink-0" />
                <p>
                    {{ __('join_organization.pending.message', ['organization' => $this->pendingOrganizationRequest->organization->short_name]) }}
                </p>
            </div>
        </div>
    @else
        <form wire:submit.prevent="submitRequest" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full">
                {{ __('profile.form.submit.label') }}
            </x-filament::button>
        </form>
    @endif

    <form method="post" action="{{ filament()->getLogoutUrl() }}" class="text-center">
        @csrf

        <x-filament::link tag="button" type="submit" color="gray" icon="heroicon-o-arrow-left-on-rectangle">
            {{ __('join_organization.logout') }}
        </x-filament::link>
    </form>
</x-filament-panels::page.simple>
