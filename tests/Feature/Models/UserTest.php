<?php

declare(strict_types=1);

use App\Models\User;

test('derives the name from the first and last name on create', function () {
    $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    expect($user->name)->toBe('Ada Lovelace')
        ->and($user->fresh()->name)->toBe('Ada Lovelace');
});

test('derives the name from the first and last name on update', function () {
    $user = User::factory()->create();

    $user->update(['first_name' => 'Grace', 'last_name' => 'Hopper']);

    expect($user->name)->toBe('Grace Hopper')
        ->and($user->fresh()->name)->toBe('Grace Hopper');
});
