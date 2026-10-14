<?php

declare(strict_types=1);

use App\Livewire\Profile\PersonalInfo;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('saves the first and last name', function () {
    $user = User::factory()->create();
    actingAs($user);

    livewire(PersonalInfo::class)
        ->assertSchemaStateSet(['first_name' => $user->first_name, 'last_name' => $user->last_name])
        ->fillForm(['first_name' => 'Grace', 'last_name' => 'Hopper', 'email' => $user->email])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->fresh())
        ->first_name->toBe('Grace')
        ->last_name->toBe('Hopper')
        ->name->toBe('Grace Hopper');
});

test('requires the first and last name', function () {
    actingAs(User::factory()->create());

    livewire(PersonalInfo::class)
        ->fillForm(['first_name' => null, 'last_name' => null])
        ->call('submit')
        ->assertHasFormErrors(['first_name' => 'required', 'last_name' => 'required']);
});
