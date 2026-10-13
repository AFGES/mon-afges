<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;

use function Pest\Laravel\travelTo;

test('defaults the email to the user email when none is provided', function () {
    $delegate = Delegate::factory()
        ->forUser(['email' => 'abc@example.com'])
        ->create(['email' => null]);

    expect($delegate->email)->toBe('abc@example.com');
});

test('keeps the email when one is explicitly provided', function () {
    $delegate = Delegate::factory()
        ->forUser(['email' => 'user@example.com'])
        ->create(['email' => 'custom@example.com']);

    expect($delegate->email)->toBe('custom@example.com');
});

test('verifies the email when it matches the verified email of the user', function () {
    travelTo('2026-03-15 12:00:00');
    $delegate = Delegate::factory()
        ->forUser(['email' => 'verified@example.com', 'email_verified_at' => now()])
        ->create(['email' => null, 'email_verified_at' => null]);

    expect($delegate)
        ->email->toBe('verified@example.com')
        ->email_verified_at->toDateTimeString()->toBe('2026-03-15 12:00:00')
        ->hasVerifiedEmail()->toBeTrue();
});

test('leaves the email unverified when the user email is not verified', function () {
    $delegate = Delegate::factory()
        ->for(User::factory()->unverified()->state(['email' => 'unverified@example.com']), 'user')
        ->create(['email' => null, 'email_verified_at' => null]);

    expect($delegate)
        ->email->toBe('unverified@example.com')
        ->email_verified_at->toBeNull()
        ->hasVerifiedEmail()->toBeFalse();
});

test('leaves the email unverified when it differs from the verified email of the user', function () {
    $delegate = Delegate::factory()
        ->forUser(['email' => 'user@example.com', 'email_verified_at' => now()])
        ->create(['email' => 'different@example.com']);

    expect($delegate)
        ->email->toBe('different@example.com')
        ->email_verified_at->toBeNull()
        ->hasVerifiedEmail()->toBeFalse();
});

test('only returns the delegates that are currently active', function () {
    travelTo('2026-03-15 12:00:00');
    $activeDelegate = Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);
    Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(5),
    ]);

    $delegates = Delegate::all();

    expect($delegates->pluck('id')->all())->toBe([$activeDelegate->id]);
});

test('reads its dates without time', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => '2026-03-01 15:30:00',
        'ended_at' => '2026-12-31 18:00:00',
    ]);

    expect($delegate->refresh())
        ->started_at->toDateTimeString()->toBe('2026-03-01 00:00:00')
        ->ended_at->toDateTimeString()->toBe('2026-12-31 00:00:00');
});
