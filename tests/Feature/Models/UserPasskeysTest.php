<?php

declare(strict_types=1);

use App\Models\User;

test('stores passkeys against the uuid of their user', function () {
    $user = User::factory()->create();

    $passkey = createPasskeyFor($user);

    expect($passkey->fresh()->user_id)->toBe($user->id)
        ->and($user->passkeys()->sole()->is($passkey))->toBeTrue()
        ->and($user->hasPasskeysEnabled())->toBeTrue();
});

test('deletes the passkeys of a deleted user', function () {
    $user = User::factory()->create();
    $passkey = createPasskeyFor($user);

    $user->delete();

    $this->assertModelMissing($passkey);
});
