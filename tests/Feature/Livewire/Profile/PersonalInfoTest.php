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
            'address_street' => $user->address_street,
            'address_postcode' => $user->address_postcode,
            'address_city' => $user->address_city,
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
            'address_street' => '1 rue de la Paix',
            'address_postcode' => '75002',
            'address_city' => 'Paris',
            'elected_mandates' => 'CFVU',
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->fresh())
        ->study_program->toBe('Master 2 Droit')
        ->birth_date->toDateString()->toBe('2000-05-17')
        ->phone->toBe('+33601020304')
        ->address_street->toBe('1 rue de la Paix')
        ->address_postcode->toBe('75002')
        ->address_city->toBe('Paris')
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
        ->fillForm(['study_program' => null, 'birth_date' => null, 'phone' => null, 'address_street' => null, 'address_postcode' => null, 'address_city' => null])
        ->call('submit')
        ->assertHasFormErrors([
            'study_program' => 'required',
            'birth_date' => 'required',
            'phone' => 'required',
            'address_street' => 'required',
            'address_postcode' => 'required',
            'address_city' => 'required',
        ]);
})->with(['pending', 'approved']);

test('lets a user without a current or pending delegate seat clear the personal details', function () {
    $user = User::factory()->withDelegateFormData()->create();
    Delegate::factory()->rejected()->for($user)->create();
    actingAs($user);

    livewire(PersonalInfo::class)
        ->fillForm(['study_program' => null, 'birth_date' => null, 'phone' => null, 'address_street' => null, 'address_postcode' => null, 'address_city' => null])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->study_program->toBeNull()
        ->phone->toBeNull();
});

test('fills the address fields from a selected search result', function () {
    actingAs(User::factory()->create());

    $result = [
        'coordinate' => ['lat' => 48.8685, 'lng' => 2.3318],
        'type' => 'house',
        'addresstype' => 'building',
        'name' => '',
        'display_name' => '12, Rue de la Paix, Paris, France',
        'address' => ['house_number' => '12', 'road' => 'Rue de la Paix', 'postcode' => '75002', 'city' => 'Paris', 'country_code' => 'fr'],
        'boundingbox' => [],
    ];

    livewire(PersonalInfo::class)
        ->set('data.address_search', json_encode($result))
        ->assertSet('data.address_street', '12 Rue de la Paix')
        ->assertSet('data.address_postcode', '75002')
        ->assertSet('data.address_city', 'Paris')
        ->assertSet('data.address_country', 'FR')
        ->assertSet('data.address_latitude', 48.8685)
        ->assertSet('data.address_longitude', 2.3318);
});

test('forgets the coordinates when the address is edited by hand', function (string $field) {
    $user = User::factory()->withDelegateFormData()->create();
    actingAs($user);

    livewire(PersonalInfo::class)
        ->set("data.{$field}", 'Modified')
        ->assertSet('data.address_latitude', null)
        ->assertSet('data.address_longitude', null);
})->with(['address_street', 'address_postcode', 'address_city']);

test('rejects tampered hidden address values', function (string $field, mixed $value) {
    actingAs(User::factory()->create());

    livewire(PersonalInfo::class)
        ->set("data.{$field}", $value)
        ->call('submit')
        ->assertHasFormErrors([$field]);
})->with([
    'country too long' => ['address_country', 'FRA'],
    'country lowercase' => ['address_country', 'fr'],
    'latitude out of range' => ['address_latitude', 91],
    'latitude not numeric' => ['address_latitude', 'abc'],
    'longitude out of range' => ['address_longitude', -181],
]);
