<x-filament::section
    :aside="true"
    :heading="__('filament-passkeys::passkeys.passkeys')"
    :description="__('filament-passkeys::passkeys.description')"
>
    @if($this->hasRecentPasswordConfirmation)
        <livewire:filament-passkeys />
    @else
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('profile.manage_passkeys.reauthenticate.body') }}
            </p>

            @if(auth()->user()->hasPasskeysEnabled())
                <div
                    x-data="{
                        isConfirming: false,
                        async confirm() {
                            this.isConfirming = true

                            try {
                                if (! window.PublicKeyCredential?.parseRequestOptionsFromJSON) {
                                    throw new Error('parseRequestOptionsFromJSON is not supported')
                                }

                                const headers = { Accept: 'application/json' }

                                const optionsResponse = await fetch(@js(route('passkey.confirm-options')), {
                                    headers,
                                    credentials: 'same-origin',
                                })

                                if (! optionsResponse.ok) {
                                    throw new Error('Unable to fetch passkey options')
                                }

                                const json = await optionsResponse.json()
                                const publicKey = PublicKeyCredential.parseRequestOptionsFromJSON(json.options)
                                const credential = await navigator.credentials.get({ publicKey })

                                const confirmResponse = await fetch(@js(route('passkey.confirm')), {
                                    method: 'POST',
                                    headers: {
                                        ...headers,
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': @js(csrf_token()),
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({ credential: credential.toJSON() }),
                                })

                                if (! confirmResponse.ok) {
                                    throw new Error('Passkey confirmation rejected')
                                }

                                await $wire.$refresh()
                            } catch (error) {
                                if (['NotAllowedError', 'AbortError'].includes(error?.name)) {
                                    return
                                }

                                new FilamentNotification()
                                    .title(@js(__('profile.manage_passkeys.reauthenticate.confirm_failed')))
                                    .danger()
                                    .send()
                            } finally {
                                this.isConfirming = false
                            }
                        },
                    }"
                >
                    <x-filament::button
                        type="button"
                        icon="heroicon-o-finger-print"
                        x-on:click="confirm"
                        x-bind:disabled="isConfirming"
                    >
                        {{ __('profile.manage_passkeys.reauthenticate.confirm_action') }}
                    </x-filament::button>
                </div>
            @endif

            <form method="post" action="{{ filament()->getLogoutUrl() }}">
                @csrf

                <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-left-on-rectangle">
                    {{ __('profile.manage_passkeys.reauthenticate.action') }}
                </x-filament::button>
            </form>
        </div>
    @endif
</x-filament::section>
