<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('delegate_form.widget.heading') }}</x-slot>
        <x-slot name="description">{{ __('delegate_form.widget.description') }}</x-slot>

        @if ($pendingCount > 0)
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ trans_choice('delegate_form.widget.pending', $pendingCount, ['count' => $pendingCount]) }}
            </p>
        @else
            <x-filament::button tag="a" :href="$requestUrl">
                {{ __('delegate_form.widget.action') }}
            </x-filament::button>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
