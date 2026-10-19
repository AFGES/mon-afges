<?php

declare(strict_types=1);

use App\Livewire\Profile\PersonalInfo;
use App\Models\Delegate;
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

test('pre-fills the personal details', function () {
    $user = User::factory()->withDelegateFormData()->create(['birth_date' => '1999-03-08']);
    actingAs($user);

    livewire(PersonalInfo::class)
        ->assertSchemaStateSet([
            'study_program' => $user->study_program,
            'birth_date' => '1999-03-08',
            'phone' => $user->phone,
            'postal_address' => $user->postal_address,
            'elected_mandates' => $user->elected_mandates,
        ]);
});

test('saves the personal details', function () {
    $user = User::factory()->create();
    actingAs($user);

    livewire(PersonalInfo::class)
        ->fillForm([
            'study_program' => 'Master 2 Droit',
            'birth_date' => '2000-05-17',
            'phone' => '+33601020304',
            'postal_address' => '1 rue de la Paix, Paris',
            'elected_mandates' => 'CFVU',
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->fresh())
        ->study_program->toBe('Master 2 Droit')
        ->birth_date->toDateString()->toBe('2000-05-17')
        ->phone->toBe('+33601020304')
        ->postal_address->toBe('1 rue de la Paix, Paris')
        ->elected_mandates->toBe('CFVU');
});

test('rejects invalid personal details', function (array $data, array $errors) {
    actingAs(User::factory()->create());

    livewire(PersonalInfo::class)
        ->fillForm($data)
        ->call('submit')
        ->assertHasFormErrors($errors);
})->with([
    '`phone` is a valid phone number' => [['phone' => '123'], ['phone']],
    '`birth_date` is not in the future' => [['birth_date' => '2999-01-01'], ['birth_date']],
]);

test('keeps the personal details required for a current or pending delegate', function (string $state) {
    $user = User::factory()->withDelegateFormData()->create();
    Delegate::factory()->{$state}()->for($user)->create();
    actingAs($user);

    livewire(PersonalInfo::class)
        ->fillForm(['study_program' => null, 'birth_date' => null, 'phone' => null, 'postal_address' => null])
        ->call('submit')
        ->assertHasFormErrors([
            'study_program' => 'required',
            'birth_date' => 'required',
            'phone' => 'required',
            'postal_address' => 'required',
        ]);
})->with(['pending', 'approved']);

test('lets a user without a current or pending delegate seat clear the personal details', function () {
    $user = User::factory()->withDelegateFormData()->create();
    Delegate::factory()->rejected()->for($user)->create();
    actingAs($user);

    livewire(PersonalInfo::class)
        ->fillForm(['study_program' => null, 'birth_date' => null, 'phone' => null, 'postal_address' => null])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->study_program->toBeNull()
        ->phone->toBeNull();
});
