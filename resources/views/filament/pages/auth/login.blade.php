@php
    use Filament\Support\Facades\FilamentView;
@endphp

<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ FilamentView::renderHook('panels::auth.login.form.before') }}

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <div class="mt-4 text-center">
        <button
            type="button"
            wire:click="toggleMagicLinkForm"
            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium underline underline-offset-4 outline-none transition duration-75 hover:text-primary-500 focus-visible:text-primary-500 dark:hover:text-primary-400 dark:focus-visible:text-primary-400"
        >
            @if ($showMagicLinkForm)
                Se connecter avec un mot de passe
            @else
                Se connecter avec un lien magique
            @endif
        </button>
    </div>

    {{ FilamentView::renderHook('panels::auth.login.form.after') }}
</x-filament-panels::page.simple>
