<x-filament::section 
    :aside="true" 
    :heading="__('select-organization.title')" 
    :description="__('Gérez vos affiliations aux associations')"
>
    <form wire:submit.prevent="submitOrganizations" class="space-y-6">
        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit">
                {{ __('select-organization.actions.submit.label') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
