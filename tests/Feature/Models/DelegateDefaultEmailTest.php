<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;

test('delegate email defaults to user email when not provided', function () {
    $delegate = Delegate::factory()
        ->forUser(['email' => 'abc@example.com'])
        ->create([
            'email' => null,
            'started_at' => now(),
        ]);

    expect($delegate->email)->toBe('abc@example.com');
});

test('delegate email is not overwritten when explicitly provided', function () {
    $delegate = Delegate::factory()
        ->forUser(['email' => 'user@example.com'])
        ->create([    
            'email' => 'custom@example.com',
        ]);

    expect($delegate->email)->toBe('custom@example.com');
});

test('delegate email is marked as verified when user email is verified and matches', function () {
    $delegate = Delegate::factory()
        ->forUser([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ])
        ->create([
            'email' => null,
            'email_verified_at' => null,
        ]);

    expect($delegate->email)->toBe('verified@example.com')
        ->and($delegate->email_verified_at)->not->toBeNull()
        ->and($delegate->hasVerifiedEmail())->toBeTrue();
});

test('delegate email is not marked as verified when user email is not verified', function () {
    $delegate = Delegate::factory()
        ->forUser([
            'email' => 'unverified@example.com',
            'email_verified_at' => null
        ])
        ->create([
            'email' => null,
            'email_verified_at' => null,
        ]);

    expect($delegate->email)->toBe('unverified@example.com')
        ->and($delegate->email_verified_at)->toBeNull()
        ->and($delegate->hasVerifiedEmail())->toBeFalse();
});

test('delegate email is not marked as verified when emails do not match', function () {
    $user = User::factory()->create();

    $delegate = Delegate::factory()
        ->forUser([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ])
        ->create([
            'user_id' => $user->id,
            'email' => 'different@example.com',
        ]);

    expect($delegate->email)->toBe('different@example.com')
        ->and($delegate->email_verified_at)->toBeNull()
        ->and($delegate->hasVerifiedEmail())->toBeFalse();
});
