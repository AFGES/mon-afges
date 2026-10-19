<?php

declare(strict_types=1);

use App\Livewire\Profile\ManagePasskeys;
use App\Models\User;
use MarcelWeidum\Passkeys\Livewire\Passkeys;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('is shown on the profile page', function () {
    actingAs(User::factory()->create(['email' => 'test@afges.org']));

    $this->get(route('filament.root.pages.my-profile'))
        ->assertOk()
        ->assertSeeLivewire(ManagePasskeys::class);
});

test('asks to log in again instead of managing passkeys without a recent confirmation', function () {
    actingAs(User::factory()->create());

    livewire(ManagePasskeys::class)
        ->assertSee(__('profile.manage_passkeys.reauthenticate.body'))
        ->assertDontSeeLivewire(Passkeys::class);
});

test('offers to confirm with a passkey when the user has one', function () {
    $user = User::factory()->create();
    createPasskeyFor($user);
    actingAs($user);

    livewire(ManagePasskeys::class)
        ->assertSee(__('profile.manage_passkeys.reauthenticate.confirm_action'));
});

test('does not offer passkey confirmation to a user without passkeys', function () {
    actingAs(User::factory()->create());

    livewire(ManagePasskeys::class)
        ->assertDontSee(__('profile.manage_passkeys.reauthenticate.confirm_action'));
});

test('embeds the passkey manager after a recent confirmation', function () {
    actingAs(User::factory()->create());
    session()->passwordConfirmed();

    livewire(ManagePasskeys::class)
        ->assertDontSee(__('profile.manage_passkeys.reauthenticate.body'))
        ->assertSeeLivewire(Passkeys::class);
});

test('lists only the passkeys of the user', function () {
    $user = User::factory()->create();
    createPasskeyFor($user, 'My laptop');
    createPasskeyFor(User::factory()->create(), 'Someone else phone');
    actingAs($user);

    livewire(Passkeys::class)
        ->assertSee('My laptop')
        ->assertDontSee('Someone else phone');
});

test('deletes a passkey of the user', function () {
    $user = User::factory()->create();
    $passkey = createPasskeyFor($user, 'My laptop');
    actingAs($user);

    livewire(Passkeys::class)
        ->call('deletePasskey', $passkey->id)
        ->assertNotified(__('filament-passkeys::passkeys.deleted_notification_title'));

    $this->assertModelMissing($passkey);
});

test('refuses to delete a passkey of another user', function () {
    $passkey = createPasskeyFor(User::factory()->create(), 'Someone else phone');
    actingAs(User::factory()->create());

    livewire(Passkeys::class)
        ->call('deletePasskey', $passkey->id)
        ->assertForbidden();

    $this->assertModelExists($passkey);
});
