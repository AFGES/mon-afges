<div
    x-data="{
        loginUrl: @js(filament()->getCurrentOrDefaultPanel()->getUrl()),
        async login() {
            try {
                await window.FilamentPasskeys.login(this.loginUrl)
            } catch (error) {
                if (['UserCancelledError', 'NotAllowedError'].includes(error?.name)) {
                    return
                }

                new FilamentNotification()
                    .title(@js(__('filament-passkeys::passkeys.invalid')))
                    .danger()
                    .send()
            }
        },
    }"
>
    <x-filament::button
        type="button"
        icon="heroicon-o-key"
        color="gray"
        class="w-full"
        x-on:click="login"
    >
        {{ __('filament-passkeys::passkeys.authenticate_using_passkey') }}
    </x-filament::button>
</div>
