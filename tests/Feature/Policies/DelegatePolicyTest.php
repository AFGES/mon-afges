<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;

test('delegate policy allows user to terminate their own approved active mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeTrue();
});

test('delegate policy denies terminating another users mandate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $otherUser->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('delegate policy denies terminating pending mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->create([
        'user_id' => $user->id,
        'approval_status' => App\Enums\ApprovalStatus::Pending,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('delegate policy denies terminating already ended mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(5),
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('delegate policy denies terminating rejected mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->rejected()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});
