@php
    $isSeatHolder = $seats->isNotEmpty();
@endphp

<x-filament-widgets::widget>
    @if ($isSeatHolder || $canRequest || $pendingCount > 0)
        <x-filament::section>
            <x-slot name="heading">{{ $isSeatHolder ? __('delegate_seat_widget.seat_holder.heading') : __('delegate_seat_widget.heading') }}</x-slot>
            <x-slot name="description">{{ $isSeatHolder ? __('delegate_seat_widget.seat_holder.description') : __('delegate_seat_widget.description') }}</x-slot>

            @if ($isSeatHolder)
                <ul class="mb-4 space-y-3">
                    @foreach ($seats as $seat)
                        <li class="flex items-start justify-between gap-2 text-sm">
                            <span>
                                <strong>{{ $seat->organization->short_name }}</strong>
                                @if ($seat->seat)
                                    - {{ $seat->seat->collegeOrganization->college->name }}, {{ __('profile.manage_delegates.seat_label', ['number' => $seat->seat->seat_number]) }}
                                @endif
                                <br>
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    @if ($seat->started_at->isFuture())
                                        {{ __('delegate_seat_widget.seat_holder.starts_on', ['date' => $seat->started_at->format('d/m/Y H:i')]) }}
                                    @else
                                        {{ __('delegate_seat_widget.seat_holder.since', ['date' => $seat->started_at->format('d/m/Y H:i')]) }}
                                    @endif
                                    @if ($seat->ended_at)
                                        - {{ __('delegate_seat_widget.seat_holder.ends_on', ['date' => $seat->ended_at->format('d/m/Y H:i')]) }}
                                    @endif
                                </span>
                            </span>
                            {{ ($this->terminateSeatAction)(['delegate' => $seat->id]) }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($pendingCount > 0)
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ trans_choice('delegate_seat_widget.pending', $pendingCount, ['count' => $pendingCount]) }}
                </p>
            @endif

            @if ($canRequest)
                @if ($isSeatHolder)
                    {{ $this->changeSeatAction }}
                @else
                    <x-filament::button tag="a" :href="$requestUrl">
                        {{ __('delegate_seat_widget.action') }}
                    </x-filament::button>
                @endif
            @endif
        </x-filament::section>
    @endif

    <x-filament-actions::modals />
</x-filament-widgets::widget>
