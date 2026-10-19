<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\travel;

it('returns login options bound to the application host to guests', function () {
    getJson(route('passkey.login-options'))
        ->assertOk()
        ->assertJsonPath('options.rpId', 'localhost');
});

it('refuses registration options with 423 when the password was not confirmed', function () {
    actingAs(User::factory()->create());

    getJson(route('passkey.registration-options'))->assertStatus(423);
});

it('returns registration options after a recent password confirmation', function () {
    $this->freezeTime();
    actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => now()->unix()]);

    getJson(route('passkey.registration-options'))
        ->assertOk()
        ->assertJsonPath('options.rp.id', 'localhost');
});

it('refuses registration options with 423 once the password confirmation timed out', function () {
    $this->freezeTime();
    actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => now()->unix()]);
    travel(10_801)->seconds();

    getJson(route('passkey.registration-options'))->assertStatus(423);
});

it('allows a passkey login for a user who can access the panel', function () {
    $passkey = createPasskeyFor(User::factory()->create(['email' => 'jane@example.com']));

    expect(Passkeys::allowsLogin(Request::create('/passkeys/login', 'POST'), Passkey::find($passkey->id)))->toBeTrue();
});
